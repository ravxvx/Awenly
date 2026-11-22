<?php
/**
 * Plugin Name: Awenly Practitioner Directory
 * Description: Self-service practitioner listings for Awenly Circle, built into BuddyBoss: a "My Practice" profile tab, admin approval, a filterable directory and listing pages. Free listings.
 * Version:     1.6.1
 * Author:      Awenly Circle
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: awenly-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWD_VERSION', '1.6.1' );
define( 'AWD_FILE', __FILE__ );
define( 'AWD_DIR', plugin_dir_path( __FILE__ ) );
define( 'AWD_URI', plugin_dir_url( __FILE__ ) );
define( 'AWD_CPT', 'awenly_practitioner' );
define( 'AWD_TAX', 'awenly_specialty' );

require_once AWD_DIR . 'includes/core.php';
require_once AWD_DIR . 'includes/form.php';
require_once AWD_DIR . 'includes/frontend.php';
require_once AWD_DIR . 'includes/admin.php';
require_once AWD_DIR . 'includes/reviews.php';
require_once AWD_DIR . 'includes/extras.php';
require_once AWD_DIR . 'includes/profile.php';
require_once AWD_DIR . 'includes/engage.php';
require_once AWD_DIR . 'includes/contact.php';
require_once AWD_DIR . 'includes/events.php';
require_once AWD_DIR . 'includes/about.php';
require_once AWD_DIR . 'includes/rebrand.php';
require_once AWD_DIR . 'includes/seo-fixes.php';
require_once AWD_DIR . 'includes/blog-faq.php';

register_activation_hook( __FILE__, function () {
	awd_register_types();
	awd_ensure_terms();
	flush_rewrite_rules();
} );
