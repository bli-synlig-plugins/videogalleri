<?php

if (!defined('ABSPATH')) exit;

class VG_Ux_Builder {

	public static function init() {
		add_action('ux_builder_setup', array(__CLASS__, 'register'));
	}

	public static function register() {
		if (!function_exists('add_ux_builder_shortcode')) {
			return;
		}

		add_ux_builder_shortcode('videogalleri', array(
			'name'      => 'Videogalleri',
			'category'  => 'Content',
			'icon'      => 'dashicons-video-alt3',
			'info'      => 'Horisontal videoslider med lenke til videoundersider.',
			'options'   => array(
				'innhold_options' => array(
					'type'    => 'group',
					'heading' => 'Innhold',
					'options' => array(
						'prosjekt_id' => array(
							'type'        => 'textfield',
							'heading'     => 'Filtrer på prosjekt-ID',
							'description' => 'Valgfritt. Vis kun videoer koblet til dette prosjektet.',
							'default'     => '',
						),
					),
				),
			),
		));

		add_ux_builder_shortcode('videogalleri_spiller', array(
			'name'     => 'Videospiller',
			'category' => 'Content',
			'icon'     => 'dashicons-format-video',
			'info'     => 'Frittstående spiller for YouTube, Vimeo og Kontainer.',
			'wrap'     => false,
			'options'  => self::player_options(),
		));
	}

	private static function player_options() {
		return array(
			'innhold_options' => array(
				'type'    => 'group',
				'heading' => 'Innhold',
				'options' => array(
					'kilde' => array(
						'type'        => 'textarea',
						'heading'     => 'Videolenke',
						'description' => 'YouTube, Vimeo eller Kontainer. Du kan også lime inn iframe- eller video-kode. Kontainer-iframe, YouTube og Vimeo bruker sine egne kontroller. Avrunding gjelder likevel.',
						'full_width'  => true,
						'default'     => '',
					),
				),
			),
			'avspilling_options' => array(
				'type'    => 'group',
				'heading' => 'Avspilling',
				'options' => array(
					'autoplay' => array(
						'type'        => 'checkbox',
						'heading'     => 'Autoavspilling',
						'description' => 'Gjelder videofiler. Skru også på dempet, fordi nettlesere blokkerer lyd ved autoavspilling.',
						'default'     => 'false',
					),
					'dempet' => array(
						'type'    => 'checkbox',
						'heading' => 'Dempet',
						'default' => 'false',
					),
					'loop' => array(
						'type'    => 'checkbox',
						'heading' => 'Gjenta',
						'default' => 'false',
					),
				),
			),
			'utseende_options' => array(
				'type'    => 'group',
				'heading' => 'Utseende',
				'options' => array(
					'radius' => array(
						'type'    => 'slider',
						'heading' => 'Avrunding',
						'default' => '16',
						'min'     => '0',
						'max'     => '48',
						'step'    => '1',
						'unit'    => 'px',
					),
					'farge' => array(
						'type'        => 'colorpicker',
						'heading'     => 'Kontrollfarge',
						'description' => 'Farge på avspillingsknapp, fremdrift og spolepunkt. Gjelder videofiler.',
						'default'     => '',
						'format'      => 'rgb',
						'alpha'       => true,
						'helpers'     => self::color_helpers(),
					),
				),
			),
			'advanced_options' => array(
				'type'    => 'group',
				'heading' => 'Avansert',
				'options' => array(
					'class' => array(
						'type'       => 'textfield',
						'heading'    => 'Klasse',
						'default'    => '',
						'full_width' => true,
					),
					'id' => array(
						'type'    => 'textfield',
						'heading' => 'ID',
						'default' => '',
					),
				),
			),
		);
	}

	private static function color_helpers() {
		$file = get_template_directory() . '/inc/builder/shortcodes/helpers/colors.php';
		if (is_readable($file)) {
			$helpers = require $file;
			if (is_array($helpers) && $helpers !== array()) {
				return $helpers;
			}
		}

		return array(
			array('title' => 'Dark', 'value' => 'rgb(0,0,0)'),
			array('title' => 'White', 'value' => 'rgb(255,255,255)'),
			array('title' => 'Primary', 'value' => ''),
			array('title' => 'Secondary', 'value' => ''),
			array('title' => 'Success', 'value' => ''),
		);
	}
}
