<?php

if (!defined('ABSPATH')) exit;

class VG_Repository {

	public static function get_link_types() {
		return array(
			'page'          => 'Side',
			'post'          => 'Blogginnlegg',
			'featured_item' => 'Portefølje',
		);
	}

	public static function get_link_type_label($type) {
		$types = self::get_link_types();
		return isset($types[$type]) ? $types[$type] : $type;
	}

	public static function get_info($post_id) {
		return (string) get_post_meta($post_id, VG_META_INFO, true);
	}

	public static function set_info($post_id, $info) {
		$info = sanitize_textarea_field($info);
		if ($info !== '') {
			update_post_meta($post_id, VG_META_INFO, $info);
		} else {
			delete_post_meta($post_id, VG_META_INFO);
		}
	}

	public static function get_video_url($post_id) {
		return (string) get_post_meta($post_id, VG_META_VIDEO_URL, true);
	}

	public static function set_video_url($post_id, $url) {
		$url = esc_url_raw(trim((string) $url));
		if ($url !== '' && self::parse_video_url($url)) {
			update_post_meta($post_id, VG_META_VIDEO_URL, $url);
			self::sync_video_meta($post_id, $url);
		} else {
			delete_post_meta($post_id, VG_META_VIDEO_URL);
			delete_post_meta($post_id, VG_META_DURATION);
			delete_post_meta($post_id, VG_META_ASPECT_RATIO);
		}
	}

	public static function get_aspect_ratio($post_id) {
		$ratio = (string) get_post_meta($post_id, VG_META_ASPECT_RATIO, true);
		if ($ratio !== '' && preg_match('/^\d+(\.\d+)?%$/', $ratio)) {
			return $ratio;
		}

		$video = self::parse_video_url(self::get_video_url($post_id));
		if ($video && !empty($video['is_vertical'])) {
			return '177.7778%';
		}

		return '56.25%';
	}

	public static function set_aspect_ratio($post_id, $width, $height) {
		$width = (float) $width;
		$height = (float) $height;
		if ($width <= 0 || $height <= 0) {
			return;
		}
		$ratio = round(($height / $width) * 100, 4) . '%';
		update_post_meta($post_id, VG_META_ASPECT_RATIO, $ratio);
	}

	public static function get_duration($post_id) {
		return max(0, (int) get_post_meta($post_id, VG_META_DURATION, true));
	}

	public static function set_duration($post_id, $seconds) {
		$seconds = max(0, (int) $seconds);
		if ($seconds > 0) {
			update_post_meta($post_id, VG_META_DURATION, $seconds);
		} else {
			delete_post_meta($post_id, VG_META_DURATION);
		}
	}

	public static function format_duration($seconds) {
		$seconds = max(0, (int) $seconds);
		if ($seconds <= 0) {
			return '';
		}

		$hours = (int) floor($seconds / 3600);
		$minutes = (int) floor(($seconds % 3600) / 60);
		$secs = $seconds % 60;

		if ($hours > 0) {
			return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
		}

		return sprintf('%d:%02d', $minutes, $secs);
	}

	public static function sync_video_meta($post_id, $url) {
		$video = self::parse_video_url($url);
		$oembed = self::fetch_oembed_data($url, $video);

		if ($oembed) {
			if (!empty($oembed['duration'])) {
				self::set_duration($post_id, (int) $oembed['duration']);
			}

			if (!empty($oembed['width']) && !empty($oembed['height'])) {
				self::set_aspect_ratio($post_id, $oembed['width'], $oembed['height']);
			}

			if (!has_post_thumbnail($post_id) && !empty($oembed['thumbnail_url'])) {
				self::maybe_set_thumbnail_from_url($post_id, $oembed['thumbnail_url']);
			}
		}

		if ($video && $video['provider'] === 'vimeo' && self::get_duration($post_id) === 0) {
			$duration = self::fetch_vimeo_duration($video['id'], $url);
			if ($duration > 0) {
				self::set_duration($post_id, $duration);
			}
		}

		if ($video && $video['provider'] === 'youtube') {
			if (self::get_duration($post_id) === 0) {
				$duration = self::fetch_youtube_duration($video['id']);
				if ($duration > 0) {
					self::set_duration($post_id, $duration);
				}
			}

			if (get_post_meta($post_id, VG_META_ASPECT_RATIO, true) === '' && !empty($video['is_vertical'])) {
				self::set_aspect_ratio($post_id, 9, 16);
			}
		}
	}

	/**
	 * noembed returnerer varighet for Vimeo, men ikke YouTube.
	 */
	private static function fetch_youtube_duration($video_id) {
		$video_id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $video_id);
		if ($video_id === '') {
			return 0;
		}

		$response = wp_remote_get(
			'https://www.youtube.com/watch?v=' . rawurlencode($video_id),
			array(
				'timeout' => 12,
				'headers' => array(
					'User-Agent' => 'Mozilla/5.0 (compatible; Videogalleri/1.0)',
				),
			)
		);

		if (is_wp_error($response)) {
			return 0;
		}

		$body = wp_remote_retrieve_body($response);
		if (!is_string($body) || $body === '') {
			return 0;
		}

		if (preg_match('/"lengthSeconds"\s*:\s*"(\d+)"/', $body, $matches)) {
			return (int) $matches[1];
		}

		return 0;
	}

	/**
	 * @param array{provider:string, id:string, embed_url:string}|null $video
	 * @return array<string, mixed>|null
	 */
	private static function fetch_oembed_data($url, $video = null) {
		if ($video === null) {
			$video = self::parse_video_url($url);
		}

		if ($video && $video['provider'] === 'vimeo') {
			$data = self::fetch_vimeo_oembed($url);
			if ($data) {
				return $data;
			}
		}

		return self::fetch_noembed($url);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function fetch_vimeo_oembed($url) {
		$oembed_url = self::get_vimeo_oembed_request_url($url);
		if ($oembed_url === '') {
			return null;
		}

		return self::fetch_json(
			'https://vimeo.com/api/oembed.json?url=' . rawurlencode($oembed_url),
			array(
				'Referer' => home_url('/'),
			)
		);
	}

	private static function fetch_noembed($url) {
		return self::fetch_json('https://noembed.com/embed?url=' . rawurlencode($url));
	}

	private static function fetch_vimeo_duration($video_id, $url) {
		$video_id = preg_replace('/\D/', '', (string) $video_id);
		if ($video_id === '') {
			return 0;
		}

		$data = self::fetch_json('https://vimeo.com/api/v2/video/' . $video_id . '.json');
		if (is_array($data) && !empty($data[0]['duration'])) {
			return (int) $data[0]['duration'];
		}

		$oembed = self::fetch_vimeo_oembed($url);
		if (is_array($oembed) && !empty($oembed['duration'])) {
			return (int) $oembed['duration'];
		}

		return 0;
	}

	private static function get_vimeo_oembed_request_url($url) {
		$url = trim((string) $url);
		if ($url === '') {
			return '';
		}

		if (preg_match('#https?://(?:www\.)?vimeo\.com/\d+(?:/[a-zA-Z0-9]+)?#', $url, $matches)) {
			return $matches[0];
		}

		if (preg_match('#(?:player\.)?vimeo\.com/(?:video/)?(\d+)(?:/([a-zA-Z0-9]+))?#', $url, $matches)) {
			$request = 'https://vimeo.com/' . $matches[1];
			if (!empty($matches[2])) {
				$request .= '/' . $matches[2];
			}
			return $request;
		}

		return $url;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private static function fetch_json($url, array $headers = array()) {
		$response = wp_remote_get($url, array(
			'timeout' => 12,
			'headers' => array_merge(
				array(
					'User-Agent' => 'Mozilla/5.0 (compatible; Videogalleri/' . VG_VERSION . ')',
				),
				$headers
			),
		));

		if (is_wp_error($response)) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		if ($code < 200 || $code >= 300) {
			return null;
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);
		if (!is_array($data) || !empty($data['error'])) {
			return null;
		}

		return $data;
	}

	private static function maybe_set_thumbnail_from_url($post_id, $image_url) {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image(esc_url_raw($image_url), $post_id, null, 'id');
		if (!is_wp_error($attachment_id)) {
			set_post_thumbnail($post_id, (int) $attachment_id);
		}
	}

	public static function get_thumbnail_url($post_id, $video = null) {
		$thumb = get_the_post_thumbnail_url($post_id, 'medium_large');
		if ($thumb) {
			return $thumb;
		}

		if (!$video) {
			$video = self::parse_video_url(self::get_video_url($post_id));
		}

		if (!$video) {
			return '';
		}

		if ($video['provider'] === 'youtube') {
			return 'https://img.youtube.com/vi/' . $video['id'] . '/hqdefault.jpg';
		}

		$oembed = self::fetch_oembed_data(self::get_video_url($post_id));
		return (!empty($oembed['thumbnail_url'])) ? $oembed['thumbnail_url'] : '';
	}

	public static function get_link_type($post_id) {
		$type = get_post_meta($post_id, VG_META_LINK_TYPE, true);
		$types = self::get_link_types();
		return array_key_exists($type, $types) ? $type : '';
	}

	public static function get_link_id($post_id) {
		return (int) get_post_meta($post_id, VG_META_LINK_ID, true);
	}

	public static function set_link($post_id, $type, $link_id) {
		$types = self::get_link_types();
		$type = sanitize_key($type);
		$link_id = (int) $link_id;

		if (!$type || !array_key_exists($type, $types) || $link_id <= 0) {
			delete_post_meta($post_id, VG_META_LINK_TYPE);
			delete_post_meta($post_id, VG_META_LINK_ID);
			return;
		}

		$linked = get_post($link_id);
		if (!$linked || $linked->post_type !== $type || $linked->post_status !== 'publish') {
			delete_post_meta($post_id, VG_META_LINK_TYPE);
			delete_post_meta($post_id, VG_META_LINK_ID);
			return;
		}

		update_post_meta($post_id, VG_META_LINK_TYPE, $type);
		update_post_meta($post_id, VG_META_LINK_ID, $link_id);
	}

	public static function get_project_url($post_id) {
		$type = self::get_link_type($post_id);
		$id = self::get_link_id($post_id);

		if (!$type || $id <= 0) {
			return '';
		}

		$linked = get_post($id);
		if (!$linked || $linked->post_type !== $type || $linked->post_status !== 'publish') {
			return '';
		}

		return get_permalink($id);
	}

	public static function get_linked_post($post_id) {
		$type = self::get_link_type($post_id);
		$id = self::get_link_id($post_id);

		if (!$type || $id <= 0) {
			return null;
		}

		$linked = get_post($id);
		if (!$linked || $linked->post_type !== $type) {
			return null;
		}

		return $linked;
	}

	/**
	 * @return array{provider:string, id:string, embed_url:string}|null
	 */
	public static function parse_video_url($url) {
		$url = trim((string) $url);
		if ($url === '') {
			return null;
		}

		if (preg_match('#(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/|youtube\.com/shorts/)([a-zA-Z0-9_-]{11})#', $url, $matches)) {
			$id = $matches[1];
			$is_short = (bool) preg_match('#youtube\.com/shorts/#', $url);
			return array(
				'provider'     => 'youtube',
				'id'           => $id,
				'is_vertical'  => $is_short,
				'embed_url'    => 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0&modestbranding=1&playsinline=1',
			);
		}

		if (preg_match('#(?:player\.)?vimeo\.com/(?:video/)?(\d+)(?:/([a-zA-Z0-9]+))?#', $url, $matches)) {
			$id = $matches[1];
			$hash = !empty($matches[2]) ? $matches[2] : '';

			$embed_url = 'https://player.vimeo.com/video/' . $id;
			$query = array(
				'title'    => '0',
				'byline'   => '0',
				'portrait' => '0',
			);
			if ($hash !== '') {
				$query['h'] = $hash;
			}

			return array(
				'provider'  => 'vimeo',
				'id'        => $id,
				'embed_url' => $embed_url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986),
			);
		}

		return null;
	}

	/**
	 * Finn publiserte videoer koblet til en side, et blogginnlegg eller porteføljeelement.
	 *
	 * @return WP_Post[]
	 */
	public static function get_by_linked_post($linked_id, array $args = array()) {
		$linked_id = (int) $linked_id;
		if ($linked_id <= 0) {
			return array();
		}

		$video_id = isset($args['video_id']) ? (int) $args['video_id'] : 0;

		$query_args = array(
			'post_type'      => VG_POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => VG_META_VIDEO_URL,
					'compare' => 'EXISTS',
				),
				array(
					'key'     => VG_META_VIDEO_URL,
					'value'   => '',
					'compare' => '!=',
				),
				array(
					'key'   => VG_META_LINK_ID,
					'value' => $linked_id,
					'type'  => 'NUMERIC',
				),
			),
		);

		if ($video_id > 0) {
			$query_args['post__in'] = array($video_id);
			$query_args['posts_per_page'] = 1;
		}

		return get_posts($query_args);
	}

	/**
	 * @return WP_Post|null
	 */
	public static function get_video_post($video_id) {
		$video_id = (int) $video_id;
		if ($video_id <= 0) {
			return null;
		}

		$post = get_post($video_id);
		if (!$post || $post->post_type !== VG_POST_TYPE || $post->post_status !== 'publish') {
			return null;
		}

		if (self::parse_video_url(self::get_video_url($video_id)) === null) {
			return null;
		}

		return $post;
	}

	/**
	 * @return WP_Post[]
	 */
	public static function get_published(array $args = array()) {
		$limit = isset($args['posts_per_page']) ? (int) $args['posts_per_page'] : -1;
		$project_id = isset($args['project_id']) ? (int) $args['project_id'] : 0;
		$exclude_id = isset($args['exclude_id']) ? (int) $args['exclude_id'] : 0;

		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => VG_META_VIDEO_URL,
				'compare' => 'EXISTS',
			),
			array(
				'key'     => VG_META_VIDEO_URL,
				'value'   => '',
				'compare' => '!=',
			),
		);

		if ($project_id > 0) {
			$meta_query[] = array(
				'key'   => VG_META_LINK_ID,
				'value' => $project_id,
				'type'  => 'NUMERIC',
			);
		}

		$query_args = array(
			'post_type'      => VG_POST_TYPE,
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'meta_query'     => $meta_query,
		);

		if ($exclude_id > 0) {
			$query_args['post__not_in'] = array($exclude_id);
		}

		return get_posts($query_args);
	}

	/**
	 * @return WP_Post[]
	 */
	public static function get_all_for_order() {
		return get_posts(array(
			'post_type'      => VG_POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		));
	}

	/**
	 * @return array{id:int, title:string, subtitle:string}|null
	 */
	public static function format_linkable_post(WP_Post $post) {
		$subtitle = '';

		if ($post->post_type === 'page') {
			if ($post->post_name !== '') {
				$subtitle = '/' . $post->post_name;
			}
		} elseif ($post->post_type === 'post') {
			$subtitle = get_the_date('', $post);
		} elseif ($post->post_type === 'featured_item') {
			$terms = get_the_terms($post->ID, 'featured_item_category');
			if (!is_wp_error($terms) && !empty($terms)) {
				$subtitle = $terms[0]->name;
			}
		}

		return array(
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'subtitle' => $subtitle,
		);
	}

	/**
	 * @return array<int, array{id:int, title:string, subtitle:string}>
	 */
	public static function search_linkable_posts($type, $search, $limit = 20) {
		$types = self::get_link_types();
		if (!array_key_exists($type, $types)) {
			return array();
		}

		$search = sanitize_text_field($search);
		if (strlen($search) < 2) {
			return array();
		}

		$query = new WP_Query(array(
			'post_type'              => $type,
			'post_status'            => 'publish',
			'posts_per_page'         => min(30, max(1, (int) $limit)),
			's'                      => $search,
			'orderby'                => 'relevance',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => ($type === 'featured_item'),
		));

		$items = array();
		foreach ($query->posts as $post) {
			$items[] = self::format_linkable_post($post);
		}

		return $items;
	}

	public static function save_order(array $order) {
		global $wpdb;

		foreach ($order as $index => $post_id) {
			$post_id = (int) $post_id;
			if ($post_id <= 0 || get_post_type($post_id) !== VG_POST_TYPE) {
				continue;
			}

			$wpdb->update(
				$wpdb->posts,
				array('menu_order' => $index + 1),
				array('ID' => $post_id),
				array('%d'),
				array('%d')
			);
			clean_post_cache($post_id);
		}
	}

	public static function format_item(WP_Post $post) {
		$linked = self::get_linked_post($post->ID);
		$video = self::parse_video_url(self::get_video_url($post->ID));
		$duration = self::get_duration($post->ID);

		return array(
			'id'              => $post->ID,
			'title'           => get_the_title($post),
			'info'            => self::get_info($post->ID),
			'video_url'       => self::get_video_url($post->ID),
			'video'           => $video,
			'permalink'       => get_permalink($post),
			'aspect_ratio'    => self::get_aspect_ratio($post->ID),
			'duration'        => $duration,
			'duration_label'  => self::format_duration($duration),
			'project_url'     => self::get_project_url($post->ID),
			'link_type'       => self::get_link_type($post->ID),
			'link_label'      => self::get_link_type_label(self::get_link_type($post->ID)),
			'linked'          => $linked ? array(
				'id'    => $linked->ID,
				'title' => $linked->post_title,
			) : null,
			'thumbnail'       => self::get_thumbnail_url($post->ID, $video),
		);
	}
}
