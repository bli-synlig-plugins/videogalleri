<?php

if (!defined('ABSPATH')) exit;

class VG_Icons {

	public static function play($size = 28, $class = '', $hidden = false) {
		return self::svg(
			'play',
			$size,
			'<path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"/>',
			$class,
			$hidden
		);
	}

	public static function pause($size = 16, $class = '', $hidden = false) {
		return self::svg(
			'pause',
			$size,
			'<rect x="14" y="4" width="4" height="16" rx="1"/><rect x="6" y="4" width="4" height="16" rx="1"/>',
			$class,
			$hidden
		);
	}

	public static function volume($size = 18, $class = '', $hidden = false) {
		return self::svg(
			'volume-2',
			$size,
			'<path d="M11 4.702a.705.705 0 0 0-1.203-.498L6.413 7.587A1.4 1.4 0 0 1 5.416 8H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.416a1.4 1.4 0 0 1 .997.413l3.383 3.384A.705.705 0 0 0 11 19.298z"/><path d="M16 9a5 5 0 0 1 0 6"/><path d="M19.364 18.364a9 9 0 0 0 0-12.728"/>',
			$class,
			$hidden
		);
	}

	public static function volume_low($size = 18, $class = '', $hidden = false) {
		return self::svg(
			'volume-1',
			$size,
			'<path d="M11 4.702a.705.705 0 0 0-1.203-.498L6.413 7.587A1.4 1.4 0 0 1 5.416 8H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.416a1.4 1.4 0 0 1 .997.413l3.383 3.384A.705.705 0 0 0 11 19.298z"/><path d="M16 9a5 5 0 0 1 0 6"/>',
			$class,
			$hidden
		);
	}

	public static function volume_off($size = 18, $class = '', $hidden = false) {
		return self::svg(
			'volume-x',
			$size,
			'<path d="M11 4.702a.705.705 0 0 0-1.203-.498L6.413 7.587A1.4 1.4 0 0 1 5.416 8H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.416a1.4 1.4 0 0 1 .997.413l3.383 3.384A.705.705 0 0 0 11 19.298z"/><line x1="22" x2="16" y1="9" y2="15"/><line x1="16" x2="22" y1="9" y2="15"/>',
			$class,
			$hidden
		);
	}

	public static function maximize($size = 18, $class = '', $hidden = false) {
		return self::svg(
			'maximize',
			$size,
			'<path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/>',
			$class,
			$hidden
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

	private static function svg($name, $size, $inner, $class = '', $hidden = false) {
		$classes = 'lucide lucide-' . $name;
		if ($class !== '') {
			$classes .= ' ' . $class;
		}

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="%2$s" aria-hidden="true"%4$s>%3$s</svg>',
			(int) $size,
			esc_attr($classes),
			$inner,
			$hidden ? ' hidden' : ''
		);
	}
}
