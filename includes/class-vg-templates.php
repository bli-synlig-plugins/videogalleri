<?php

if (!defined('ABSPATH')) exit;

class VG_Templates {

	public static function init() {
		add_filter('template_include', array(__CLASS__, 'load_single_template'));
	}

	public static function load_single_template($template) {
		if (!is_singular(VG_POST_TYPE)) {
			return $template;
		}

		$file = VG_PLUGIN_DIR . 'templates/single-videogalleri.php';
		if (file_exists($file)) {
			return $file;
		}

		return $template;
	}
}
