<?php

if (!defined('ABSPATH')) exit;

class VG_Admin {

	public static function init() {
		add_action('add_meta_boxes', array(__CLASS__, 'register_meta_boxes'));
		add_action('save_post_' . VG_POST_TYPE, array(__CLASS__, 'save_video'), 10, 2);
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
		add_action('admin_menu', array(__CLASS__, 'register_order_page'));
		add_action('wp_ajax_vg_search_posts', array(__CLASS__, 'ajax_search_posts'));
		add_action('wp_ajax_vg_save_order', array(__CLASS__, 'ajax_save_order'));
		add_filter('manage_' . VG_POST_TYPE . '_posts_columns', array(__CLASS__, 'columns'));
		add_action('manage_' . VG_POST_TYPE . '_posts_custom_column', array(__CLASS__, 'column_content'), 10, 2);
	}

	public static function enqueue_assets($hook) {
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen) {
			return;
		}

		$is_edit_screen = in_array($hook, array('post.php', 'post-new.php'), true) && $screen->post_type === VG_POST_TYPE;
		$is_order_page  = $hook === VG_POST_TYPE . '_page_vg-rekkefolge';

		if (!$is_edit_screen && !$is_order_page) {
			return;
		}

		wp_enqueue_style(
			'vg-admin',
			VG_PLUGIN_URL . 'assets/admin.css',
			array(),
			VG_VERSION
		);

		if ($is_edit_screen) {
			wp_enqueue_script(
				'vg-admin',
				VG_PLUGIN_URL . 'assets/admin.js',
				array('jquery'),
				VG_VERSION,
				true
			);
			wp_localize_script('vg-admin', 'vgAdmin', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('vg_admin'),
				'i18n'    => array(
					'noResults' => 'Ingen treff. Prøv et annet søkeord.',
					'loadError' => 'Kunne ikke søke. Prøv igjen.',
				),
			));
		}

		if ($is_order_page) {
			wp_enqueue_script(
				'vg-admin-order',
				VG_PLUGIN_URL . 'assets/admin.js',
				array('jquery', 'jquery-ui-sortable'),
				VG_VERSION,
				true
			);
			wp_localize_script('vg-admin-order', 'vgOrder', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce'   => wp_create_nonce('vg_save_order'),
				'i18n'    => array(
					'saving' => 'Lagrer…',
					'saved'  => 'Rekkefølge lagret.',
					'error'  => 'Kunne ikke lagre.',
				),
			));
		}
	}

	public static function register_meta_boxes() {
		add_meta_box(
			'vg_video_meta',
			'Video',
			array(__CLASS__, 'render_video_metabox'),
			VG_POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'vg_link_meta',
			'Knytt til prosjekt',
			array(__CLASS__, 'render_link_metabox'),
			VG_POST_TYPE,
			'normal',
			'default'
		);
	}

	public static function render_video_metabox($post) {
		wp_nonce_field('vg_save_video', 'vg_video_nonce');

		$info = VG_Repository::get_info($post->ID);
		$url = VG_Repository::get_video_url($post->ID);
		$duration = VG_Repository::format_duration(VG_Repository::get_duration($post->ID));
		?>
		<p class="description" style="margin-top:0;">
			Lim inn en YouTube- eller Vimeo-lenke. Varighet og thumbnail hentes automatisk ved lagring.
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vg_video_url">Videolenke</label></th>
				<td>
					<input
						type="url"
						name="vg_video_url"
						id="vg_video_url"
						class="large-text"
						value="<?php echo esc_attr($url); ?>"
						placeholder="https://www.youtube.com/watch?v=…"
					>
					<?php if ($duration !== '') : ?>
						<p class="description">Varighet: <?php echo esc_html($duration); ?></p>
					<?php else : ?>
						<p class="description">Varighet ble ikke hentet. Lagre på nytt, eller sjekk at Vimeo-lenken er offentlig/embargbar.</p>
					<?php endif; ?>
					<?php if ($post->post_status === 'publish') : ?>
						<p class="description">
							<a href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener noreferrer">
								Vis videounderside ↗
							</a>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vg_info">Kort info</label></th>
				<td>
					<textarea
						name="vg_info"
						id="vg_info"
						class="large-text"
						rows="3"
						placeholder="Kort beskrivelse som vises under videoen…"
					><?php echo esc_textarea($info); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function render_link_metabox($post) {
		$link_type = VG_Repository::get_link_type($post->ID);
		$link_id   = VG_Repository::get_link_id($post->ID);
		$linked    = VG_Repository::get_linked_post($post->ID);
		$selected  = $linked ? VG_Repository::format_linkable_post($linked) : null;
		?>
		<p class="description" style="margin-top:0;">
			Koble videoen til et prosjekt, en side eller et blogginnlegg.
		</p>
		<?php if ($linked) : ?>
			<p class="description">
				For å vise videoen i innholdet, bruk shortcoden
				<code>[videogalleri_video]</code>
				på siden eller i innlegget.
			</p>
		<?php endif; ?>
		<table class="form-table vg-link-table" role="presentation">
			<tr>
				<th scope="row"><label for="vg_link_type">Innholdstype</label></th>
				<td>
					<select name="vg_link_type" id="vg_link_type" class="regular-text">
						<option value="">— Velg type —</option>
						<?php foreach (VG_Repository::get_link_types() as $value => $label) : ?>
							<option value="<?php echo esc_attr($value); ?>" <?php selected($link_type, $value); ?>>
								<?php echo esc_html($label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr id="vg_link_post_row" <?php echo $link_type ? '' : 'style="display:none;"'; ?>>
				<th scope="row"><label for="vg_link_search">Velg innhold</label></th>
				<td>
					<div class="vg-link-picker" data-vg-link-picker>
						<input type="hidden" name="vg_link_id" id="vg_link_id" value="<?php echo esc_attr($link_id); ?>">

						<div class="vg-link-picker__selected" data-vg-link-selected <?php echo $selected ? '' : 'hidden'; ?>>
							<div class="vg-link-picker__selected-body">
								<strong data-vg-link-selected-title><?php echo $selected ? esc_html($selected['title']) : ''; ?></strong>
								<span class="vg-link-picker__selected-sub" data-vg-link-selected-sub><?php echo ($selected && $selected['subtitle'] !== '') ? esc_html($selected['subtitle']) : ''; ?></span>
							</div>
							<button type="button" class="button-link-delete" data-vg-link-remove>Fjern</button>
						</div>

						<div class="vg-link-picker__search-wrap" data-vg-link-search-wrap <?php echo $selected ? 'hidden' : ''; ?>>
							<input
								type="search"
								id="vg_link_search"
								class="vg-link-picker__search"
								placeholder="Søk etter tittel…"
								data-vg-link-search
								autocomplete="off"
							>
							<p class="description" data-vg-link-hint>Skriv minst 2 tegn for å søke.</p>
							<div class="vg-link-picker__results" data-vg-link-results hidden></div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function save_video($post_id, $post) {
		if (!isset($_POST['vg_video_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vg_video_nonce'])), 'vg_save_video')) {
			return;
		}
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		$url = isset($_POST['vg_video_url']) ? wp_unslash($_POST['vg_video_url']) : '';
		VG_Repository::set_video_url($post_id, $url);

		$info = isset($_POST['vg_info']) ? wp_unslash($_POST['vg_info']) : '';
		VG_Repository::set_info($post_id, $info);

		$type = isset($_POST['vg_link_type']) ? sanitize_key(wp_unslash($_POST['vg_link_type'])) : '';
		$id   = isset($_POST['vg_link_id']) ? (int) $_POST['vg_link_id'] : 0;
		VG_Repository::set_link($post_id, $type, $id);
	}

	public static function ajax_search_posts() {
		check_ajax_referer('vg_admin', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error(array('message' => 'Ingen tilgang'), 403);
		}

		$type = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
		if (!array_key_exists($type, VG_Repository::get_link_types())) {
			wp_send_json_error(array('message' => 'Ugyldig innholdstype'), 400);
		}

		$term = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
		if (strlen($term) < 2) {
			wp_send_json_success(array('items' => array()));
		}

		wp_send_json_success(array(
			'items' => VG_Repository::search_linkable_posts($type, $term),
		));
	}

	public static function register_order_page() {
		add_submenu_page(
			'edit.php?post_type=' . VG_POST_TYPE,
			'Rekkefølge',
			'Rekkefølge',
			'edit_posts',
			'vg-rekkefolge',
			array(__CLASS__, 'render_order_page')
		);
	}

	public static function ajax_save_order() {
		check_ajax_referer('vg_save_order', 'nonce');

		if (!current_user_can('edit_posts')) {
			wp_send_json_error(array('message' => 'Ingen tilgang'), 403);
		}

		$order = isset($_POST['order']) ? (array) wp_unslash($_POST['order']) : array();
		$order = array_values(array_filter(array_map('intval', $order)));

		VG_Repository::save_order($order);
		wp_send_json_success();
	}

	public static function render_order_page() {
		if (!current_user_can('edit_posts')) {
			wp_die('Ingen tilgang');
		}

		$posts = VG_Repository::get_all_for_order();
		?>
		<div class="wrap vg-order-wrap">
			<h1>Rekkefølge i videogalleri</h1>

			<?php if (empty($posts)) : ?>
				<p>Ingen videoer ennå.</p>
			<?php else : ?>
				<div class="vg-order-bar">
					<p class="vg-order-intro">
						Dra radene i den rekkefølgen du vil vise dem i galleriet.
					</p>
					<div class="vg-order-actions">
						<span id="vg-order-status" class="vg-order-status" aria-live="polite"></span>
						<button type="button" id="vg-order-save" class="button button-primary" disabled>
							Lagre rekkefølge
						</button>
					</div>
				</div>

				<ul id="vg-order-list" class="vg-order-list">
					<?php foreach ($posts as $index => $post) :
						$item = VG_Repository::format_item($post);
						$linked = $item['linked'];

						$status_label = '';
						if ($post->post_status !== 'publish') {
							$status_obj = get_post_status_object($post->post_status);
							$status_label = $status_obj ? $status_obj->label : $post->post_status;
						}

						$thumb = get_the_post_thumbnail($post->ID, array(40, 40), array(
							'class' => 'vg-order-thumb',
							'alt'   => '',
						));
						?>
						<li class="vg-order-item" data-id="<?php echo esc_attr($post->ID); ?>">
							<span class="vg-order-handle" title="Dra for å flytte" aria-label="Dra for å endre rekkefølge">
								<span class="dashicons dashicons-menu"></span>
							</span>
							<span class="vg-order-num"><?php echo esc_html($index + 1); ?></span>
							<?php if ($thumb) : ?>
								<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<span class="vg-order-thumb vg-order-thumb--empty" aria-hidden="true">
									<span class="dashicons dashicons-video-alt3"></span>
								</span>
							<?php endif; ?>
							<span class="vg-order-meta">
								<span class="vg-order-name"><?php echo esc_html($post->post_title); ?></span>
								<?php if ($linked) : ?>
									<span class="vg-order-sub">
										<?php echo esc_html($item['link_label'] . ': ' . $linked['title']); ?>
									</span>
								<?php elseif (!$item['video']) : ?>
									<span class="vg-order-sub" style="color:#b32d2e;">Mangler gyldig videolenke</span>
								<?php endif; ?>
							</span>
							<?php if ($status_label) : ?>
								<span class="vg-order-badge"><?php echo esc_html($status_label); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function columns($columns) {
		$new = array();
		foreach ($columns as $key => $label) {
			$new[$key] = $label;
			if ($key === 'title') {
				$new['vg_video'] = 'Video';
				$new['vg_duration'] = 'Varighet';
				$new['vg_link']  = 'Koblet til';
			}
		}
		return $new;
	}

	public static function column_content($column, $post_id) {
		if ($column === 'vg_video') {
			$video = VG_Repository::parse_video_url(VG_Repository::get_video_url($post_id));
			if (!$video) {
				echo '<span style="color:#b32d2e;">—</span>';
				return;
			}
			echo esc_html(ucfirst($video['provider']));
			return;
		}

		if ($column === 'vg_duration') {
			$label = VG_Repository::format_duration(VG_Repository::get_duration($post_id));
			echo $label !== '' ? esc_html($label) : '<span aria-hidden="true">—</span>';
			return;
		}

		if ($column === 'vg_link') {
			$item = VG_Repository::format_item(get_post($post_id));
			$linked = $item['linked'];

			if (!$linked) {
				echo '<span style="color:#646970;">—</span>';
				return;
			}

			printf(
				'%s: <a href="%s">%s</a>',
				esc_html($item['link_label']),
				esc_url(get_edit_post_link($linked['id'])),
				esc_html($linked['title'])
			);
		}
	}
}
