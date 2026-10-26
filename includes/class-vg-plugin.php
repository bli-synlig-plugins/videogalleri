<?php

if (!defined('ABSPATH')) exit;

class VG_Plugin {

	public static function init() {
		VG_CPT::init();
		VG_Settings::init();
		VG_Admin::init();
		VG_Frontend::init();
		VG_Templates::init();
		VG_Ux_Builder::init();
	}

	public static function activate() {
		VG_CPT::register_post_type();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
