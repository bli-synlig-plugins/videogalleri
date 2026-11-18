<?php

if (!defined('ABSPATH')) exit;

class VG_Settings {

	const OPTION_PLAY_COLOR         = 'vg_play_color';
	const OPTION_SINGLE_BG          = 'vg_single_bg';
	const OPTION_MORE_BG            = 'vg_more_bg';
	const OPTION_PROJECT_BTN_COLOR  = 'vg_project_btn_color';
	const OPTION_PROJECT_BTN_SIZE   = 'vg_project_btn_size';

	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'register_page'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
		add_action('wp_ajax_vg_save_settings', array(__CLASS__, 'ajax_save'));
	}

	public static function register_page() {
		add_submenu_page(
			'edit.php?post_type=' . VG_POST_TYPE,
			'Innstillinger',
			'Innstillinger',
			'manage_options',
			'vg-innstillinger',
			array(__CLASS__, 'render_page')
		);
	}

	public static function get_button_colors() {
		return array(
			'primary'   => 'Primær',
			'secondary' => 'Sekundær',
		);
	}

	public static function get_button_sizes() {
		return array(
			''        => 'Normal',
			'small'   => 'Small',
			'large'   => 'Large',
			'larger'  => 'Larger',
			'xlarge'  => 'X-Large',
		);
	}

	public static function enqueue_assets($hook) {
		if ($hook !== VG_POST_TYPE . '_page_vg-innstillinger') {
			return;
		}

		wp_enqueue_style(
			'vg-admin-settings',
			VG_PLUGIN_URL . 'assets/admin-settings.css',
			array('wp-color-picker'),
			VG_VERSION
		);
		wp_enqueue_script(
			'vg-settings',
			VG_PLUGIN_URL . 'assets/admin-settings.js',
			array('wp-color-picker', 'jquery'),
			VG_VERSION,
			true
		);
		wp_localize_script('vg-settings', 'vgSettings', array(
			'settings' => self::export_settings(),
			'ajaxUrl'  => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('vg_save_settings'),
			'i18n'     => array(
				'saved' => 'Endringene er lagret',
				'error' => 'Kunne ikke lagre innstillingene. Prøv igjen.',
				'saving' => 'Lagrer…',
				'save'  => 'Lagre endringer',
			),
		));
	}

	public static function ajax_save() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Ingen tilgang.'), 403);
		}

		check_ajax_referer('vg_save_settings', 'nonce');

		$raw = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '';
		if (is_string($raw)) {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : array();
		}
		if (!is_array($raw)) {
			$raw = array();
		}

		$clean = self::sanitize_settings($raw);
		foreach ($clean as $key => $value) {
			update_option($key, $value);
		}

		wp_send_json_success(array(
			'settings' => self::export_settings(),
			'message'  => 'Endringene er lagret',
		));
	}

	public static function sanitize_settings(array $raw) {
		return array(
			self::OPTION_PLAY_COLOR        => self::sanitize_color(isset($raw[self::OPTION_PLAY_COLOR]) ? $raw[self::OPTION_PLAY_COLOR] : ''),
			self::OPTION_SINGLE_BG         => self::sanitize_color(isset($raw[self::OPTION_SINGLE_BG]) ? $raw[self::OPTION_SINGLE_BG] : ''),
			self::OPTION_MORE_BG           => self::sanitize_color(isset($raw[self::OPTION_MORE_BG]) ? $raw[self::OPTION_MORE_BG] : ''),
			self::OPTION_PROJECT_BTN_COLOR => self::sanitize_button_color(isset($raw[self::OPTION_PROJECT_BTN_COLOR]) ? $raw[self::OPTION_PROJECT_BTN_COLOR] : ''),
			self::OPTION_PROJECT_BTN_SIZE  => self::sanitize_button_size(isset($raw[self::OPTION_PROJECT_BTN_SIZE]) ? $raw[self::OPTION_PROJECT_BTN_SIZE] : ''),
		);
	}

	public static function export_settings() {
		return array(
			self::OPTION_PLAY_COLOR        => self::get(self::OPTION_PLAY_COLOR),
			self::OPTION_SINGLE_BG         => self::get(self::OPTION_SINGLE_BG),
			self::OPTION_MORE_BG           => self::get(self::OPTION_MORE_BG),
			self::OPTION_PROJECT_BTN_COLOR => self::get(self::OPTION_PROJECT_BTN_COLOR),
			self::OPTION_PROJECT_BTN_SIZE  => self::get(self::OPTION_PROJECT_BTN_SIZE),
		);
	}

	public static function sanitize_color($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}

		if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
			return $value;
		}

		if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value)) {
			return $value;
		}

		return '';
	}

	public static function sanitize_button_color($value) {
		$value = sanitize_key((string) $value);
		$allowed = self::get_button_colors();
		return array_key_exists($value, $allowed) ? $value : 'primary';
	}

	public static function sanitize_button_size($value) {
		$value = sanitize_key((string) $value);
		$allowed = self::get_button_sizes();
		return array_key_exists($value, $allowed) ? $value : '';
	}

	public static function get($key) {
		$defaults = array(
			self::OPTION_PLAY_COLOR        => '',
			self::OPTION_SINGLE_BG         => '',
			self::OPTION_MORE_BG           => '',
			self::OPTION_PROJECT_BTN_COLOR => 'primary',
			self::OPTION_PROJECT_BTN_SIZE  => '',
		);

		if (!array_key_exists($key, $defaults)) {
			return '';
		}

		$value = get_option($key, $defaults[$key]);
		return is_string($value) ? $value : $defaults[$key];
	}

	public static function get_project_button_classes() {
		$classes = array(
			'button',
			self::get(self::OPTION_PROJECT_BTN_COLOR),
			'vg-single__project-link',
		);

		$size = self::get(self::OPTION_PROJECT_BTN_SIZE);
		if ($size !== '') {
			$classes[] = 'is-' . $size;
		}

		return implode(' ', $classes);
	}

	public static function build_inline_style() {
		$vars = array();

		$play = self::get(self::OPTION_PLAY_COLOR);
		if ($play !== '') {
			$vars['--vg-play-color'] = $play;
		}

		$single_bg = self::get(self::OPTION_SINGLE_BG);
		if ($single_bg !== '') {
			$vars['--vg-single-bg'] = $single_bg;
		}

		$more_bg = self::get(self::OPTION_MORE_BG);
		if ($more_bg !== '') {
			$vars['--vg-more-bg'] = $more_bg;
		}

		if (empty($vars)) {
			return '';
		}

		$parts = array();
		foreach ($vars as $key => $value) {
			$parts[] = $key . ':' . $value;
		}

		return implode(';', $parts) . ';';
	}

	public static function render_page() {
		if (!current_user_can('manage_options')) {
			wp_die('Ingen tilgang');
		}

		$opts = self::export_settings();
		?>
		<div class="wrap vg-admin">
			<div class="vg-app">
				<header class="vg-top">
					<div>
						<h1>Innstillinger</h1>
						<p>Globale visningsvalg for videogalleri og videoundersider. Endringer gjelder på tvers av nettstedet.</p>
					</div>
					<span class="vg-pill">v<?php echo esc_html(VG_VERSION); ?></span>
				</header>

				<nav class="vg-nav" role="tablist" aria-label="Innstillinger">
					<button type="button" role="tab" aria-selected="true" data-tab="slider">Slider</button>
					<button type="button" role="tab" aria-selected="false" data-tab="sider">Videosider</button>
					<button type="button" role="tab" aria-selected="false" data-tab="flere">Se flere videoer</button>
					<button type="button" role="tab" aria-selected="false" data-tab="koder">Shortcodes</button>
				</nav>

				<main class="vg-main">
					<section class="vg-tab is-on" id="vg-tab-slider" role="tabpanel">
						<div class="vg-card">
							<h2>Slider</h2>
							<p class="vg-sub">Utseende på videokortene i den horisontale slideren.</p>
							<div class="vg-row">
								<div>
									<label class="vg-t" for="vg_play_color">Play-ikon – bakgrunn</label>
									<span class="vg-h">Bakgrunnsfargen på play-sirkelen. Ikonet er alltid hvitt. Tom bruker standard, semi-transparent hvit.</span>
								</div>
								<input
									type="text"
									id="vg_play_color"
									class="vg-input vg-color-field"
									data-field="<?php echo esc_attr(self::OPTION_PLAY_COLOR); ?>"
									value="<?php echo esc_attr($opts[self::OPTION_PLAY_COLOR]); ?>"
									data-default-color=""
								>
							</div>
						</div>
					</section>

					<section class="vg-tab" id="vg-tab-sider" role="tabpanel" hidden>
						<div class="vg-card">
							<h2>Videosider</h2>
							<p class="vg-sub">Utseende på enkeltsider for hver video (<code>/video/…</code>).</p>
							<div class="vg-row">
								<div>
									<label class="vg-t" for="vg_single_bg">Sidebakgrunn</label>
									<span class="vg-h">Bakgrunnsfarge på hovedinnholdet. Tom arver fra temaet.</span>
								</div>
								<input
									type="text"
									id="vg_single_bg"
									class="vg-input vg-color-field"
									data-field="<?php echo esc_attr(self::OPTION_SINGLE_BG); ?>"
									value="<?php echo esc_attr($opts[self::OPTION_SINGLE_BG]); ?>"
									data-default-color=""
								>
							</div>
							<div class="vg-row">
								<div>
									<label class="vg-t" for="vg_project_btn_color">«Se prosjekt»-knapp – farge</label>
									<span class="vg-h">Bruker Flatsome sine knappefarger.</span>
								</div>
								<select id="vg_project_btn_color" class="vg-input" data-field="<?php echo esc_attr(self::OPTION_PROJECT_BTN_COLOR); ?>">
									<?php foreach (self::get_button_colors() as $value => $label) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected($opts[self::OPTION_PROJECT_BTN_COLOR], $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="vg-row">
								<div>
									<label class="vg-t" for="vg_project_btn_size">«Se prosjekt»-knapp – størrelse</label>
									<span class="vg-h">Samme størrelser som Flatsome-knapper.</span>
								</div>
								<select id="vg_project_btn_size" class="vg-input" data-field="<?php echo esc_attr(self::OPTION_PROJECT_BTN_SIZE); ?>">
									<?php foreach (self::get_button_sizes() as $value => $label) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected($opts[self::OPTION_PROJECT_BTN_SIZE], $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</section>

					<section class="vg-tab" id="vg-tab-flere" role="tabpanel" hidden>
						<div class="vg-card">
							<h2>Seksjonen «Se flere videoer»</h2>
							<p class="vg-sub">Utseende på slideren nederst på videoundersider.</p>
							<div class="vg-row">
								<div>
									<label class="vg-t" for="vg_more_bg">Seksjonsbakgrunn</label>
									<span class="vg-h">Bakgrunnsfarge bak «Se flere videoer». Tom arver fra temaet.</span>
								</div>
								<input
									type="text"
									id="vg_more_bg"
									class="vg-input vg-color-field"
									data-field="<?php echo esc_attr(self::OPTION_MORE_BG); ?>"
									value="<?php echo esc_attr($opts[self::OPTION_MORE_BG]); ?>"
									data-default-color=""
								>
							</div>
						</div>
					</section>

					<section class="vg-tab" id="vg-tab-koder" role="tabpanel" hidden>
						<div class="vg-card">
							<h2>Shortcodes</h2>
							<p class="vg-sub">Bruk disse i innhold, UX Builder eller widgets.</p>
							<div class="vg-row">
								<div>
									<span class="vg-t">Videogalleri</span>
									<span class="vg-h">Horisontal slider med publiserte videoer. <code>prosjekt_id="123"</code> filtrerer på koblet innhold.</span>
								</div>
								<code>[videogalleri]</code>
							</div>
							<div class="vg-row">
								<div>
									<span class="vg-t">Koblet video</span>
									<span class="vg-h">Videoen som er koblet til gjeldende side. Valgfritt: <code>tittel="ja"</code>, <code>info="ja"</code>, <code>lenke="ja"</code>, <code>video_id="456"</code>.</span>
								</div>
								<code>[videogalleri_video]</code>
							</div>
							<div class="vg-row">
								<div>
									<span class="vg-t">Videospiller</span>
									<span class="vg-h">Frittstående spiller. Avrunding og kontrollfarge settes på elementet i UX Builder.</span>
								</div>
								<code>[videogalleri_spiller]</code>
							</div>
						</div>
					</section>
				</main>
			</div>

			<div class="vg-save" id="vg-save" role="region" aria-label="Ulagrede endringer">
				<span>Du har ulagrede endringer</span>
				<button type="button" class="vg-btn" id="vg-rst">Tilbakestill</button>
				<button type="button" class="vg-btn is-primary" id="vg-sv">Lagre endringer</button>
			</div>
			<div class="vg-toast" id="vg-toast" role="status">Endringene er lagret</div>
		</div>
		<?php
	}
}
