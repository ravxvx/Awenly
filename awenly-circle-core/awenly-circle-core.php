<?php
/**
 * Plugin Name: Awenly Circle Core
 * Description: Core community, practitioner directory and events foundation for Awenly Circle. Integrates with BuddyBoss when available.
 * Version: 1.0.1
 * Author: Awenly Circle
 * Text Domain: awenly-circle-core
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */
if (!defined('ABSPATH')) exit;
define('AWENLY_CORE_VERSION', '1.0.1');
define('AWENLY_CORE_PATH', plugin_dir_path(__FILE__));
define('AWENLY_CORE_URL', plugin_dir_url(__FILE__));
require_once AWENLY_CORE_PATH . 'includes/class-awenly-core.php';
register_activation_hook(__FILE__, ['Awenly_Circle_Core', 'activate']);
register_deactivation_hook(__FILE__, ['Awenly_Circle_Core', 'deactivate']);
add_action('plugins_loaded', static function () { Awenly_Circle_Core::instance(); });
