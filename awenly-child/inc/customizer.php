<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Appearance → Customize → "Awenly Circle" panel.
 */
add_action( 'customize_register', function ( $wp_customize ) {
	$d = awenly_defaults();

	$wp_customize->add_panel( 'awenly', array(
		'title'    => __( 'Awenly Circle', 'awenly-child' ),
		'priority' => 25,
	) );

	$sections = array(
		'awenly_topbar' => __( 'Top bar & social links', 'awenly-child' ),
		'awenly_hero'   => __( 'Homepage hero', 'awenly-child' ),
		'awenly_links'  => __( 'Homepage links & events', 'awenly-child' ),
	);
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, array( 'title' => $title, 'panel' => 'awenly' ) );
	}

	$fields = array(
		// key => [section, label, type]
		'topbar_text'       => array( 'awenly_topbar', __( 'Top bar text', 'awenly-child' ), 'text' ),
		'social_facebook'   => array( 'awenly_topbar', 'Facebook URL', 'url' ),
		'social_instagram'  => array( 'awenly_topbar', 'Instagram URL', 'url' ),
		'social_youtube'    => array( 'awenly_topbar', 'YouTube URL', 'url' ),
		'social_linkedin'   => array( 'awenly_topbar', 'LinkedIn URL', 'url' ),
		'join_url'          => array( 'awenly_topbar', __( '"Join the Circle" URL', 'awenly-child' ), 'url' ),
		'login_url'         => array( 'awenly_topbar', __( 'Login URL', 'awenly-child' ), 'url' ),
		'hero_tagline'      => array( 'awenly_hero', __( 'Small line under the logo', 'awenly-child' ), 'text' ),
		'hero_title'        => array( 'awenly_hero', __( 'Headline (one line per row)', 'awenly-child' ), 'textarea' ),
		'hero_text'         => array( 'awenly_hero', __( 'Description', 'awenly-child' ), 'textarea' ),
		'community_url'     => array( 'awenly_links', __( '"Join Our Community" URL', 'awenly-child' ), 'url' ),
		'events_url'        => array( 'awenly_links', __( 'Events page URL', 'awenly-child' ), 'url' ),
		'practitioners_url' => array( 'awenly_links', __( 'Practitioners page URL', 'awenly-child' ), 'url' ),
		'courses_url'       => array( 'awenly_links', __( 'Courses page URL', 'awenly-child' ), 'url' ),
		'events_count'      => array( 'awenly_links', __( 'How many events on the homepage', 'awenly-child' ), 'number' ),
	);

	foreach ( $fields as $key => $f ) {
		switch ( $f[2] ) {
			case 'url':
				$sanitize = 'esc_url_raw';
				break;
			case 'number':
				$sanitize = 'absint';
				break;
			case 'textarea':
				$sanitize = 'sanitize_textarea_field';
				break;
			default:
				$sanitize = 'sanitize_text_field';
		}
		$wp_customize->add_setting( 'awenly_' . $key, array(
			'default'           => $d[ $key ],
			'sanitize_callback' => $sanitize,
		) );
		$wp_customize->add_control( 'awenly_' . $key, array(
			'label'   => $f[1],
			'section' => $f[0],
			'type'    => $f[2],
		) );
	}

	$wp_customize->add_setting( 'awenly_hero_image', array(
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'awenly_hero_image', array(
		'label'       => __( 'Hero background image', 'awenly-child' ),
		'description' => __( 'Wide landscape photo, at least 1920×700 px. Without an image a purple sunset gradient is shown.', 'awenly-child' ),
		'section'     => 'awenly_hero',
	) ) );
} );
