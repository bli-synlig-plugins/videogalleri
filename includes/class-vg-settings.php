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
		add_action('admin_init', array(__CLASS__, 'register_settings'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
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

	public static function register_settings() {
		register_setting('vg_settings', self::OPTION_PLAY_COLOR, array(
			'type'              => 'string',
			'sanitize_callback' => array(__CLASS__, 'sanitize_color'),
			'default'           => '',
		));
		register_setting('vg_settings', self::OPTION_SINGLE_BG, array(
			'type'              => 'string',
			'sanitize_callback' => array(__CLASS__, 'sanitize_color'),
			'default'           => '',
		));
		register_setting('vg_settings', self::OPTION_MORE_BG, array(
			'type'              => 'string',
			'sanitize_callback' => array(__CLASS__, 'sanitize_color'),
			'default'           => '',
		));
		register_setting('vg_settings', self::OPTION_PROJECT_BTN_COLOR, array(
			'type'              => 'string',
			'sanitize_callback' => array(__CLASS__, 'sanitize_button_color'),
			'default'           => 'primary',
		));
		register_setting('vg_settings', self::OPTION_PROJECT_BTN_SIZE, array(
			'type'              => 'string',
			'sanitize_callback' => array(__CLASS__, 'sanitize_button_size'),
			'default'           => '',
		));
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
			array(),
			VG_VERSION
		);
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script(
			'vg-settings',
			VG_PLUGIN_URL . 'assets/admin-settings.js',
			array('wp-color-picker', 'jquery'),
			VG_VERSION,
			true
		);
	}

	public static function sanitize_color($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return '';
		}

		if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
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
		?>
		<div class="wrap vg-settings-wrap">
			<h1>Innstillinger</h1>
			<p class="vg-settings-intro">
				Globale visningsvalg for videogalleri og videoundersider. Endringer gjelder på tvers av nettstedet.
			</p>

			<form method="post" action="options.php">
				<?php settings_fields('vg_settings'); ?>

				<div class="vg-settings-section">
					<h2 class="vg-settings-section__title">Slider</h2>
					<p class="vg-settings-section__lead">Utseende på videokortene i den horisontale slideren.</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="vg_play_color">Play-ikon – bakgrunn</label></th>
							<td>
								<input
									type="text"
									name="<?php echo esc_attr(self::OPTION_PLAY_COLOR); ?>"
									id="vg_play_color"
									class="vg-color-field"
									value="<?php echo esc_attr(self::get(self::OPTION_PLAY_COLOR)); ?>"
									data-default-color=""
								>
								<p class="description">Bakgrunnsfargen på play-sirkelen. Ikonet er alltid hvitt. La stå tom for standard semi-transparent hvit.</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="vg-settings-section">
					<h2 class="vg-settings-section__title">Videosider</h2>
					<p class="vg-settings-section__lead">Utseende på enkeltsider for hver video (<code>/video/…</code>).</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="vg_single_bg">Sidebakgrunn</label></th>
							<td>
								<input
									type="text"
									name="<?php echo esc_attr(self::OPTION_SINGLE_BG); ?>"
									id="vg_single_bg"
									class="vg-color-field"
									value="<?php echo esc_attr(self::get(self::OPTION_SINGLE_BG)); ?>"
									data-default-color=""
								>
								<p class="description">Bakgrunnsfarge på hovedinnholdet på videosiden. Tom = arver fra tema.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="vg_project_btn_color">«Se prosjekt»-knapp – farge</label></th>
							<td>
								<select name="<?php echo esc_attr(self::OPTION_PROJECT_BTN_COLOR); ?>" id="vg_project_btn_color">
									<?php foreach (self::get_button_colors() as $value => $label) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected(self::get(self::OPTION_PROJECT_BTN_COLOR), $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Bruker Flatsome sine knappefarger (primary / secondary).</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="vg_project_btn_size">«Se prosjekt»-knapp – størrelse</label></th>
							<td>
								<select name="<?php echo esc_attr(self::OPTION_PROJECT_BTN_SIZE); ?>" id="vg_project_btn_size">
									<?php foreach (self::get_button_sizes() as $value => $label) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected(self::get(self::OPTION_PROJECT_BTN_SIZE), $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Samme størrelsesvalg som Flatsome-knapper (f.eks. Large, Larger).</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="vg-settings-section">
					<h2 class="vg-settings-section__title">Seksjonen «Se flere videoer»</h2>
					<p class="vg-settings-section__lead">Utseende på slideren nederst på videoundersider.</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="vg_more_bg">Seksjonsbakgrunn</label></th>
							<td>
								<input
									type="text"
									name="<?php echo esc_attr(self::OPTION_MORE_BG); ?>"
									id="vg_more_bg"
									class="vg-color-field"
									value="<?php echo esc_attr(self::get(self::OPTION_MORE_BG)); ?>"
									data-default-color=""
								>
								<p class="description">Bakgrunnsfarge bak «Se flere videoer» og tilhørende slider. Tom = arver fra tema.</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="vg-settings-section">
					<h2 class="vg-settings-section__title">Shortcodes</h2>
					<p class="vg-settings-section__lead">Bruk disse shortcodene i innhold, UX Builder eller widgets.</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Videogalleri</th>
							<td>
								<code>[videogalleri]</code>
								<p class="description">Horisontal slider med alle publiserte videoer. Valgfritt: <code>prosjekt_id="123"</code> for å filtrere på koblet innhold.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Koblet video</th>
							<td>
								<code>[videogalleri_video]</code>
								<p class="description">Viser videoen som er koblet til gjeldende side/innlegg. Valgfritt: <code>tittel="ja"</code>, <code>info="ja"</code>, <code>lenke="ja"</code>, <code>video_id="456"</code>.</p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button('Lagre innstillinger'); ?>
			</form>
		</div>
		<?php
	}
}
