<?php

if (!defined('ABSPATH')) exit;

class VG_CPT {

	public static function init() {
		add_action('init', array(__CLASS__, 'register_post_type'));
		add_action('transition_post_status', array(__CLASS__, 'assign_menu_order_on_create'), 10, 3);
	}

	public static function register_post_type() {
		register_post_type(VG_POST_TYPE, array(
			'labels' => array(
				'name'               => 'Videogalleri',
				'singular_name'      => 'Video',
				'add_new'            => 'Legg til ny video',
				'add_new_item'       => 'Legg til ny video',
				'edit_item'          => 'Rediger video',
				'new_item'           => 'Ny video',
				'view_item'          => 'Vis video',
				'search_items'       => 'Søk i videogalleri',
				'not_found'          => 'Ingen videoer funnet',
				'not_found_in_trash' => 'Ingen videoer i papirkurven',
				'menu_name'          => 'Videogalleri',
			),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-video-alt3',
			'menu_position'       => 26,
			'supports'            => array('title', 'thumbnail', 'page-attributes'),
			'has_archive'         => false,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_in_nav_menus'   => false,
			'rewrite'             => array(
				'slug'       => 'video',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		));
	}

	public static function assign_menu_order_on_create($new_status, $old_status, $post) {
		if ($post->post_type !== VG_POST_TYPE) {
			return;
		}
		if (in_array($new_status, array('auto-draft', 'inherit', 'trash'), true)) {
			return;
		}
		if (!in_array($old_status, array('new', 'auto-draft'), true)) {
			return;
		}

		global $wpdb;
		$max = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type = %s AND post_status != %s",
			VG_POST_TYPE,
			'auto-draft'
		));
		$wpdb->update($wpdb->posts, array('menu_order' => $max + 1), array('ID' => $post->ID));
		clean_post_cache($post->ID);
	}
}
