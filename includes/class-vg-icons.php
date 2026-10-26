<?php

if (!defined('ABSPATH')) exit;

class VG_Icons {

	public static function play($size = 28) {
		return self::svg(
			'play',
			$size,
			'<polygon points="6 3 20 12 6 21 6 3"></polygon>'
		);
	}

	public static function chevron_left($size = 20) {
		return self::svg(
			'chevron-left',
			$size,
			'<path d="m15 18-6-6 6-6"></path>'
		);
	}

	public static function chevron_right($size = 20) {
		return self::svg(
			'chevron-right',
			$size,
			'<path d="m9 18 6-6-6-6"></path>'
		);
	}

	public static function arrow_right($size = 18) {
		return self::svg(
			'arrow-right',
			$size,
			'<path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path>'
		);
	}

	private static function svg($name, $size, $inner) {
		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-%2$s" aria-hidden="true">%3$s</svg>',
			(int) $size,
			esc_attr($name),
			$inner
		);
	}
}
