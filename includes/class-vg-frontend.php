<?php

if (!defined('ABSPATH')) exit;

class VG_Frontend {

	public static function init() {
		add_shortcode('videogalleri', array(__CLASS__, 'shortcode_slider'));
		add_shortcode('videogalleri_video', array(__CLASS__, 'shortcode_embed'));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'maybe_enqueue_assets'), 20);
		add_action('ux_builder_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
		add_filter('body_class', array(__CLASS__, 'body_class'));
	}

	public static function body_class($classes) {
		if (is_singular(VG_POST_TYPE)) {
			$classes[] = 'vg-single-page';
		}
		return $classes;
	}

	public static function register_assets() {
		wp_register_style(
			'vg-frontend',
			VG_PLUGIN_URL . 'assets/frontend.css',
			array(),
			VG_VERSION
		);
		wp_register_script(
			'vg-frontend',
			VG_PLUGIN_URL . 'assets/frontend.js',
			array(),
			VG_VERSION,
			true
		);
	}

	public static function maybe_enqueue_assets() {
		if (self::is_ux_builder_context() || is_singular(VG_POST_TYPE)) {
			self::enqueue_assets();
			return;
		}

		if (!is_singular()) {
			return;
		}

		global $post;
		if (!$post instanceof WP_Post) {
			return;
		}

		if (
			has_shortcode($post->post_content, 'videogalleri')
			|| has_shortcode($post->post_content, 'videogalleri_video')
		) {
			self::enqueue_assets();
		}
	}

	public static function enqueue_assets() {
		self::register_assets();
		wp_enqueue_style('vg-frontend');
		wp_enqueue_script('vg-frontend');
	}

	public static function is_ux_builder_context() {
		return defined('UX_BUILDER_IFRAME')
			|| defined('UX_BUILDER_DOING_AJAX')
			|| defined('UX_BUILDER_AJAX_REQUEST');
	}

	public static function get_wrapper_style() {
		$style = VG_Settings::build_inline_style();
		return $style !== '' ? $style : '';
	}

	public static function shortcode_slider($atts) {
		$config = self::parse_slider_atts($atts);
		self::enqueue_assets();

		$query_args = array();
		if ($config['prosjekt_id'] > 0) {
			$query_args['project_id'] = $config['prosjekt_id'];
		}
		if ($config['exclude_id'] > 0) {
			$query_args['exclude_id'] = $config['exclude_id'];
		}

		$videos = VG_Repository::get_published($query_args);
		if (empty($videos) && self::is_ux_builder_context()) {
			$videos = self::get_demo_videos();
		}
		if (empty($videos)) {
			return '';
		}

		$html = self::render_slider($videos, $config);
		return self::maybe_wrap_builder_styles(self::wrap_with_settings($html));
	}

	public static function shortcode_embed($atts) {
		$config = self::parse_embed_atts($atts);
		self::enqueue_assets();

		$videos = self::resolve_embed_videos($config);
		if (empty($videos) && self::is_ux_builder_context()) {
			$videos = self::get_demo_videos();
			$config['is_demo'] = true;
		}
		if (empty($videos)) {
			return '';
		}

		$html = '';
		foreach ($videos as $video) {
			$item = !empty($config['is_demo'])
				? self::format_demo_item($video)
				: VG_Repository::format_item($video);

			if (!$item['video']) {
				continue;
			}

			$html .= self::render_embed($item, $config);
		}

		if ($html === '') {
			return '';
		}

		return self::maybe_wrap_builder_styles(self::wrap_with_settings($html));
	}

	public static function parse_embed_atts($atts) {
		$atts = shortcode_atts(array(
			'post_id'  => '',
			'video_id' => '',
			'tittel'   => 'false',
			'info'     => 'false',
			'lenke'    => 'false',
		), $atts, 'videogalleri_video');

		$post_id = (int) $atts['post_id'];
		if ($post_id <= 0 && is_singular()) {
			$post_id = (int) get_the_ID();
		}

		return array(
			'post_id'  => $post_id,
			'video_id' => (int) $atts['video_id'],
			'show_title' => self::is_truthy($atts['tittel']),
			'show_info'  => self::is_truthy($atts['info']),
			'show_link'  => self::is_truthy($atts['lenke']),
			'is_demo'    => false,
		);
	}

	private static function is_truthy($value) {
		$value = strtolower(trim((string) $value));
		return in_array($value, array('1', 'true', 'yes', 'ja', 'on'), true);
	}

	/**
	 * @return WP_Post[]
	 */
	private static function resolve_embed_videos(array $config) {
		if ($config['video_id'] > 0) {
			$video = VG_Repository::get_video_post($config['video_id']);
			return $video ? array($video) : array();
		}

		if ($config['post_id'] <= 0) {
			return array();
		}

		return VG_Repository::get_by_linked_post($config['post_id']);
	}

	/**
	 * @param object $video
	 */
	private static function format_demo_item($video) {
		return array(
			'id'           => $video->ID,
			'title'        => $video->post_title,
			'info'         => 'Demo-video fra UX Builder.',
			'permalink'    => '#',
			'aspect_ratio' => '56.25%',
			'video'        => array(
				'provider'  => $video->demo_provider,
				'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $video->demo_video_id . '?rel=0&modestbranding=1&playsinline=1',
			),
		);
	}

	public static function render_single_page($post_id) {
		self::enqueue_assets();

		$post = get_post($post_id);
		if (!$post || $post->post_type !== VG_POST_TYPE) {
			return;
		}

		$item = VG_Repository::format_item($post);
		$style = self::get_wrapper_style();

		echo '<div class="vg-page"' . ($style !== '' ? ' style="' . esc_attr($style) . '"' : '') . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if (!$item['video']) {
			echo '<p class="vg-single__empty">Denne videoen er ikke tilgjengelig.</p>';
			echo '</div>';
			return;
		}

		echo self::render_single($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$more_videos = VG_Repository::get_published(array('exclude_id' => $post_id));
		if (empty($more_videos) && self::is_ux_builder_context()) {
			$more_videos = self::get_demo_videos();
		}

		if (!empty($more_videos)) {
			$config = self::parse_slider_atts(array());
			$config['exclude_id'] = $post_id;

			echo '<section class="vg-single__more">';
			echo '<div class="vg-single__more-inner">';
			echo '<h2 class="vg-single__more-title">Se flere videoer</h2>';
			echo self::render_slider($more_videos, $config, 'vg-slider--embedded'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
			echo '</section>';
		}

		echo '</div>';
	}

	public static function parse_slider_atts($atts) {
		$atts = shortcode_atts(array(
			'prosjekt_id' => '',
			'exclude_id'  => '',
		), $atts, 'videogalleri');

		return array(
			'prosjekt_id' => (int) $atts['prosjekt_id'],
			'exclude_id'  => (int) $atts['exclude_id'],
		);
	}

	private static function wrap_with_settings($html) {
		$style = self::get_wrapper_style();
		if ($style === '') {
			return $html;
		}
		return '<div class="vg-wrap" style="' . esc_attr($style) . '">' . $html . '</div>';
	}

	/**
	 * @return object[]
	 */
	private static function get_demo_videos() {
		$items = array(
			array('title' => 'Demo: Prosjektpresentasjon', 'duration' => 154, 'provider' => 'youtube', 'id' => 'ysz5S6PUM-U'),
			array('title' => 'Demo: Bak kulissene', 'duration' => 92, 'provider' => 'youtube', 'id' => 'aqz-KE-bpKQ'),
			array('title' => 'Demo: Resultat og innsikt', 'duration' => 68, 'provider' => 'youtube', 'id' => 'ScMzIvxBSi4'),
		);

		$videos = array();
		foreach ($items as $index => $item) {
			$post = new stdClass();
			$post->ID = 800000 + $index;
			$post->post_title = $item['title'];
			$post->post_type = VG_POST_TYPE;
			$post->demo_duration = $item['duration'];
			$post->demo_provider = $item['provider'];
			$post->demo_video_id = $item['id'];
			$videos[] = $post;
		}

		return $videos;
	}

	private static function maybe_wrap_builder_styles($html) {
		if (!defined('UX_BUILDER_AJAX_REQUEST')) {
			return $html;
		}

		$css_path = VG_PLUGIN_DIR . 'assets/frontend.css';
		if (!file_exists($css_path)) {
			return $html;
		}

		return '<style>' . wp_strip_all_tags((string) file_get_contents($css_path)) . '</style>' . $html;
	}

	/**
	 * @param object[] $videos
	 */
	public static function render_slider(array $videos, array $config, $extra_class = '') {
		$classes = trim('vg-slider ' . $extra_class);

		ob_start();
		?>
		<div class="<?php echo esc_attr($classes); ?>" role="region" aria-label="Videogalleri">
			<button type="button" class="vg-slider__nav vg-slider__nav--prev" aria-label="Forrige" disabled>
				<?php echo VG_Icons::chevron_left(22); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<div class="vg-slider__viewport">
				<div class="vg-slider__track">
					<?php
					foreach ($videos as $video) {
						echo self::render_card($video, $config); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
			<button type="button" class="vg-slider__nav vg-slider__nav--next" aria-label="Neste">
				<?php echo VG_Icons::chevron_right(22); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param object $video
	 */
	private static function render_card($video, array $config) {
		$is_demo = isset($video->demo_provider);
		$item = $is_demo ? array(
			'title'          => $video->post_title,
			'permalink'      => '#',
			'duration_label' => VG_Repository::format_duration($video->demo_duration),
			'thumbnail'      => 'https://img.youtube.com/vi/' . $video->demo_video_id . '/hqdefault.jpg',
			'video'          => array('provider' => $video->demo_provider),
		) : VG_Repository::format_item($video);

		if (!$item['video']) {
			return '';
		}

		ob_start();
		?>
		<a class="vg-slider__card" href="<?php echo esc_url($item['permalink']); ?>">
			<span class="vg-slider__thumb">
				<?php if ($item['thumbnail']) : ?>
					<img src="<?php echo esc_url($item['thumbnail']); ?>" alt="" loading="lazy">
				<?php else : ?>
					<span class="vg-slider__thumb-placeholder" aria-hidden="true"></span>
				<?php endif; ?>

				<span class="vg-slider__overlay" aria-hidden="true">
					<span class="vg-slider__play">
						<?php echo VG_Icons::play(26); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="vg-slider__meta">
						<?php if ($item['duration_label'] !== '') : ?>
							<span class="vg-slider__duration"><?php echo esc_html($item['duration_label']); ?></span>
						<?php endif; ?>
						<span class="vg-slider__title"><?php echo esc_html($item['title']); ?></span>
					</span>
				</span>
			</span>
		</a>
		<?php
		return ob_get_clean();
	}

	private static function render_single(array $item) {
		$show_project = !empty($item['linked']) && $item['project_url'] !== '';

		ob_start();
		?>
		<article class="vg-single">
			<?php echo self::render_video_player($item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<div class="vg-single__content">
				<h1 class="vg-single__title"><?php echo esc_html($item['title']); ?></h1>

				<?php if (trim((string) $item['info']) !== '') : ?>
					<div class="vg-single__info"><?php echo nl2br(esc_html($item['info'])); ?></div>
				<?php endif; ?>

				<?php if ($show_project) : ?>
					<a class="<?php echo esc_attr(VG_Settings::get_project_button_classes()); ?>" href="<?php echo esc_url($item['project_url']); ?>">
						Se prosjekt
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	private static function render_embed(array $item, array $config) {
		ob_start();
		?>
		<div class="vg-embed">
			<?php echo self::render_video_player($item, 'vg-embed__video'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ($config['show_title'] || $config['show_info'] || $config['show_link']) : ?>
				<div class="vg-embed__content">
					<?php if ($config['show_title']) : ?>
						<h3 class="vg-embed__title"><?php echo esc_html($item['title']); ?></h3>
					<?php endif; ?>

					<?php if ($config['show_info'] && trim((string) $item['info']) !== '') : ?>
						<div class="vg-embed__info"><?php echo nl2br(esc_html($item['info'])); ?></div>
					<?php endif; ?>

					<?php if ($config['show_link'] && !empty($item['permalink']) && $item['permalink'] !== '#') : ?>
						<p class="vg-embed__link-wrap">
							<a class="vg-embed__link" href="<?php echo esc_url($item['permalink']); ?>">
								Se videoside
								<?php echo VG_Icons::arrow_right(16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function render_video_player(array $item, $wrapper_class = 'vg-single__video') {
		ob_start();
		?>
		<div class="<?php echo esc_attr($wrapper_class); ?>">
			<div
				class="vg-single__video-inner"
				style="padding-top:<?php echo esc_attr($item['aspect_ratio']); ?>"
			>
				<iframe
					src="<?php echo esc_url($item['video']['embed_url']); ?>"
					title="<?php echo esc_attr($item['title']); ?>"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					allowfullscreen
					loading="lazy"
				></iframe>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
