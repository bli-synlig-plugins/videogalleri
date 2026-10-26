<?php
/**
 * Plugin Name: Videogalleri
 * Plugin URI: https://wordpress-plugins-docs.vercel.app/plugins/videogalleri
 * Description: Videogalleri med horisontal slider og egne undersider per video. YouTube/Vimeo med kobling til prosjekter.
 * Version: 1.1.1
 * Author: Benjamin
 * Text Domain: videogalleri
 */

if (!defined('ABSPATH')) exit;

define('VG_VERSION', '1.1.1');
define('VG_PLUGIN_FILE', __FILE__);
define('VG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VG_POST_TYPE', 'videogalleri');
define('VG_META_INFO', '_vg_info');
define('VG_META_VIDEO_URL', '_vg_video_url');
define('VG_META_DURATION', '_vg_duration');
define('VG_META_ASPECT_RATIO', '_vg_aspect_ratio');
define('VG_META_LINK_TYPE', '_vg_link_type');
define('VG_META_LINK_ID', '_vg_link_id');

require_once VG_PLUGIN_DIR . 'includes/class-vg-plugin.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-cpt.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-repository.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-icons.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-settings.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-admin.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-frontend.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-templates.php';
require_once VG_PLUGIN_DIR . 'includes/class-vg-ux-builder.php';

register_activation_hook(__FILE__, array('VG_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('VG_Plugin', 'deactivate'));

add_action('plugins_loaded', array('VG_Plugin', 'init'));
