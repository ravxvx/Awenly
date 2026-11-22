<?php
/**
 * About page: replaces the old (LinkinEvents) Elementor layout of /about/ with an Awenly Circle page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function awd_is_about_page() {
	return is_page( array( 'about', 'about-us' ) );
}

/** Real numbers for the about page (cached for an hour). */
function awd_about_stats() {
	$s = get_transient( 'awd_about_stats' );
	if ( is_array( $s ) ) {
		return $s;
	}
	$users = count_users();
	$s     = array(
		'members'       => (int) ( $users['total_users'] ?? 0 ),
		'practitioners' => (int) ( wp_count_posts( AWD_CPT )->publish ?? 0 ),
		'events'        => function_exists( 'awd_events_active' ) && awd_events_active() ? count( tribe_get_events( array( 'posts_per_page' => 200, 'start_date' => 'now', 'fields' => 'ids' ) ) ) : 0,
		'groups'        => function_exists( 'groups_get_total_group_count' ) ? (int) groups_get_total_group_count() : 0,
	);
	set_transient( 'awd_about_stats', $s, HOUR_IN_SECONDS );
	return $s;
}

add_filter( 'template_include', function ( $template ) {
	return awd_is_about_page() ? AWD_DIR . 'includes/template-about.php' : $template;
}, 100 );

add_filter( 'body_class', function ( $c ) {
	if ( awd_is_about_page() ) {
		$c[] = 'awd-page';
	}
	return $c;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( awd_is_about_page() || is_page( array( 'add-events', 'my-events', 'event-directory' ) ) ) {
		wp_enqueue_style( 'awenly-directory', AWD_URI . 'assets/directory.css', array(), AWD_VERSION );
	}
}, 10002 );
