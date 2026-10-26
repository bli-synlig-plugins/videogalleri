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
	}
}
