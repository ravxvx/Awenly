<?php
/**
 * Awenly Circle — BuddyBoss child theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWENLY_CHILD_VERSION', '3.3.3' );
define( 'AWENLY_CHILD_DIR', get_stylesheet_directory() );
define( 'AWENLY_CHILD_URI', get_stylesheet_directory_uri() );

require_once AWENLY_CHILD_DIR . '/inc/helpers.php';
require_once AWENLY_CHILD_DIR . '/inc/customizer.php';
require_once AWENLY_CHILD_DIR . '/inc/events.php';
require_once AWENLY_CHILD_DIR . '/inc/art.php';

/**
 * Styles: parent first, then Awenly on top (late priority so we win over BuddyBoss).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'awenly-fonts',
		'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'awenly-child', AWENLY_CHILD_URI . '/assets/css/awenly.css', array(), AWENLY_CHILD_VERSION );
	wp_enqueue_script( 'awenly-child', AWENLY_CHILD_URI . '/assets/js/awenly.js', array(), AWENLY_CHILD_VERSION, true );
}, 9999 );

/**
 * Exclude our script from LiteSpeed/JS combine & defer tricks that could break it.
 */
add_filter( 'litespeed_optimize_js_excludes', function ( $list ) {
	$list[] = 'awenly-child/assets/js/awenly.js';
	return $list;
} );

add_action( 'after_setup_theme', function () {
	load_child_theme_textdomain( 'awenly-child', AWENLY_CHILD_DIR . '/languages' );
	add_image_size( 'awenly-card', 640, 360, true );
} );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'awenly-theme';
	if ( is_front_page() ) {
		$classes[] = 'awenly-home';
	}
	return $classes;
} );

/**
 * Purple top bar above the BuddyBoss header (social icons, tagline, Login / Join).
 */
/**
 * Site-wide Awenly footer (replaces the BuddyBoss footer, which is hidden in CSS).
 */
function awenly_print_footer() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	get_template_part( 'template-parts/awenly-footer' );
}
// Inside #page, right after the (hidden) BuddyBoss footer.
add_action( 'buddyboss_theme_after_footer', 'awenly_print_footer', 5 );
// Fallback for templates without that hook.
add_action( 'wp_footer', 'awenly_print_footer', 1 );

/**
 * Homepage "Join now" form → prefill the BuddyBoss registration form.
 */
add_action( 'wp_footer', function () {
	if ( empty( $_GET['aw_email'] ) && empty( $_GET['aw_name'] ) ) {
		return;
	}
	$email = sanitize_email( wp_unslash( $_GET['aw_email'] ?? '' ) );
	$name  = sanitize_text_field( wp_unslash( $_GET['aw_name'] ?? '' ) );
	?>
	<script>
	(function(){var e=<?php echo wp_json_encode( $email ); ?>,n=<?php echo wp_json_encode( $name ); ?>;
	function f(){var em=document.querySelector('input[name="signup_email"],input[name="signup_email_confirm"],#signup_email');
	var nm=document.querySelector('input[name="field_1"],#field_1');
	if(em&&e){document.querySelectorAll('input[name="signup_email"],input[name="signup_email_confirm"]').forEach(function(i){i.value=e;});}
	if(nm&&n){nm.value=n;}}
	if(document.readyState!=='loading'){f();}else{document.addEventListener('DOMContentLoaded',f);}})();
	</script>
	<?php
}, 50 );

/**
 * Use the built-in BuddyBoss registration form at /register/ instead of the old
 * "custom registration" redirect to the LinkInEvents price-plan page.
 */
add_filter( 'pre_option_allow-custom-registration', function () {
	return '0';
} );

/**
 * Awenly logo on wp-login.php (BuddyBoss login screen).
 */
add_action( 'login_enqueue_scripts', function () {
	$logo = AWENLY_CHILD_URI . '/assets/img/awenly-logo.png';
	echo '<style>
		.login h1 a, #login h1 a, .login-heading h1 a { background-image: url(' . esc_url( $logo ) . ') !important; background-size: contain !important; background-position: center !important; width: 276px !important; height: 54px !important; }
		.login h1 a img, #login h1 a img { display: none !important; }
		body.login #wp-submit, body.login .button-primary, body.login input[type=submit] { background: #c8ec3f !important; border-color: #c8ec3f !important; color: #2b1356 !important; font-weight: 600 !important; border-radius: 10px !important; box-shadow: 0 8px 22px rgba(170,210,30,.35) !important; text-shadow: none !important; }
		body.login #wp-submit:hover, body.login .button-primary:hover { background: #b6dc28 !important; border-color: #b6dc28 !important; }
		body.login a { color: #6d32c9 !important; }
		body.login input[type=text]:focus, body.login input[type=email]:focus, body.login input[type=password]:focus { border-color: #8b5cf6 !important; box-shadow: 0 0 0 3px rgba(139,92,246,.15) !important; }
		body.login input[type=checkbox]:checked { background-color: #6d32c9 !important; border-color: #6d32c9 !important; }
	</style>';
}, 99 );
add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', function () {
	return 'Awenly Circle';
} );
