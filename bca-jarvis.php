<?php
/**
 * Plugin Name: Blk Canvas - Jarvis
 * Plugin URI: [Insert the URL for the plugin's website here]
 * Description: A powerful and intuitive plugin that transforms the way you create content on your WordPress site.
 * Version: 1.0
 * Author: [Insert the name of the author here]
 * Author URI: [Insert the URL for the author's website here]
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: blk-canvas-jarvis
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BCA_JARVIS_PATH', plugin_dir_path( __FILE__ ) );
define('BCA_JARVIS_URI', plugin_dir_url( __FILE__ ) );

include_once BCA_JARVIS_PATH . '/includes/api.php';
include_once BCA_JARVIS_PATH . '/includes/admin/helpers.php';
include_once BCA_JARVIS_PATH . '/includes/admin/callbacks.php';
include_once BCA_JARVIS_PATH . '/includes/admin/admin.php';
include_once BCA_JARVIS_PATH . '/includes/admin/settings.php';
