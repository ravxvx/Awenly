<?php
/**
 * Data model: practitioner listings (shared with Awenly Circle Core), specialties, fields, BuddyBoss profile type.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Listing fields stored as post meta. Keys match Awenly Circle Core so existing data keeps working.
 */
function awd_fields() {
	return array(
		'tagline'        => array( 'label' => __( 'Short tagline', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Reiki Master & Sound Healer', 'awenly-directory' ), 'max' => 90 ),
		'location'       => array( 'label' => __( 'City / area', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Cork, Ireland', 'awenly-directory' ), 'max' => 80 ),
		'mode'           => array( 'label' => __( 'Session format', 'awenly-directory' ), 'type' => 'select', 'options' => array( 'Online' => __( 'Online', 'awenly-directory' ), 'In person' => __( 'In person', 'awenly-directory' ), 'Both' => __( 'Online & in person', 'awenly-directory' ) ) ),
		'fee'            => array( 'label' => __( 'Price from', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. €60 / session', 'awenly-directory' ), 'max' => 40 ),
		'languages'      => array( 'label' => __( 'Languages', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. English, Polish', 'awenly-directory' ), 'max' => 80 ),
		'qualifications' => array( 'label' => __( 'Training & qualifications', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Usui Reiki III, ICF coach', 'awenly-directory' ), 'max' => 160 ),
		'website'        => array( 'label' => __( 'Website', 'awenly-directory' ), 'type' => 'url', 'placeholder' => 'https://' ),
		'booking_url'    => array( 'label' => __( 'Booking link (Calendly, etc.)', 'awenly-directory' ), 'type' => 'url', 'placeholder' => 'https://' ),
	);
}

function awd_meta( $post_id, $key ) {
	return (string) get_post_meta( $post_id, '_awenly_' . $key, true );
}

/**
 * Default specialties. Slugs match the homepage chips (?s_practitioner=…).
 */
function awd_default_terms() {
	return array(
		'therapy'    => 'Therapy & Counselling',
		'coaching'   => 'Coaching',
		'reiki'      => 'Energy Healing & Reiki',
		'meditation' => 'Meditation & Mindfulness',
		'sound'      => 'Sound Healing',
		'shamanic'   => 'Shamanic Practice',
		'yoga'       => 'Yoga & Movement',
		'breathwork' => 'Breathwork',
		'wellbeing'  => 'Holistic Wellbeing',
	);
}

/**
 * Register the listing type + taxonomy only if Awenly Circle Core has not already done it.
 */
function awd_register_types() {
	if ( ! post_type_exists( AWD_CPT ) ) {
		register_post_type( AWD_CPT, array(
			'labels'       => array(
				'name'          => __( 'Practitioners', 'awenly-directory' ),
				'singular_name' => __( 'Practitioner', 'awenly-directory' ),
				'add_new_item'  => __( 'Add Practitioner', 'awenly-directory' ),
				'edit_item'     => __( 'Edit Practitioner', 'awenly-directory' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'practitioners' ),
			'menu_icon'    => 'dashicons-id',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
			'show_in_rest' => true,
		) );
	}
	add_post_type_support( AWD_CPT, 'author' );

	if ( ! taxonomy_exists( AWD_TAX ) ) {
		register_taxonomy( AWD_TAX, AWD_CPT, array(
			'labels'       => array( 'name' => __( 'Specialisations', 'awenly-directory' ), 'singular_name' => __( 'Specialisation', 'awenly-directory' ) ),
			'public'       => true,
			'hierarchical' => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'practitioner-specialty' ),
		) );
	}
}
add_action( 'init', 'awd_register_types', 20 );

function awd_ensure_terms() {
	if ( ! taxonomy_exists( AWD_TAX ) ) {
		return;
	}
	foreach ( awd_default_terms() as $slug => $name ) {
		if ( ! term_exists( $slug, AWD_TAX ) ) {
			wp_insert_term( $name, AWD_TAX, array( 'slug' => $slug ) );
		}
	}
	update_option( 'awd_terms_version', AWD_VERSION );
}
add_action( 'init', function () {
	if ( get_option( 'awd_terms_version' ) !== AWD_VERSION ) {
		awd_ensure_terms();
		flush_rewrite_rules( false );
	}
}, 30 );

/**
 * BuddyBoss / BuddyPress profile type "Practitioner".
 */
add_action( 'bp_register_member_types', function () {
	if ( function_exists( 'bp_register_member_type' ) && ! bp_get_member_type_object( 'practitioner' ) ) {
		bp_register_member_type( 'practitioner', array(
			'labels'        => array( 'name' => __( 'Practitioners', 'awenly-directory' ), 'singular_name' => __( 'Practitioner', 'awenly-directory' ) ),
			'has_directory' => 'practitioners-members',
		) );
	}
} );

/**
 * The (single) listing owned by a user, any status except trash.
 */
function awd_get_user_listing( $user_id ) {
	if ( ! $user_id ) {
		return null;
	}
	$posts = get_posts( array(
		'post_type'      => AWD_CPT,
		'author'         => (int) $user_id,
		'post_status'    => array( 'publish', 'pending', 'draft' ),
		'posts_per_page' => 1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
	return $posts ? $posts[0] : null;
}

/**
 * URL of the "My Practice" tab for the current user (or registration if logged out).
 */
function awd_practice_url() {
	if ( is_user_logged_in() && function_exists( 'bp_loggedin_user_domain' ) ) {
		return trailingslashit( bp_loggedin_user_domain() ) . 'practice/';
	}
	if ( is_user_logged_in() ) {
		return admin_url( 'post-new.php?post_type=' . AWD_CPT );
	}
	return function_exists( 'bp_get_signup_page' ) ? bp_get_signup_page() : wp_registration_url();
}

function awd_directory_url() {
	$url = get_post_type_archive_link( AWD_CPT );
	return $url ? $url : home_url( '/practitioners/' );
}

/**
 * Community profile URL of the listing owner.
 */
function awd_owner_profile_url( $post ) {
	$uid = (int) $post->post_author;
	if ( $uid && function_exists( 'bp_core_get_user_domain' ) ) {
		return bp_core_get_user_domain( $uid );
	}
	return '';
}

/**
 * "Send a message" URL (BuddyBoss compose with recipient prefilled).
 */
function awd_message_url( $post ) {
	$uid = (int) $post->post_author;
	if ( ! $uid || ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'messages' ) ) {
		return '';
	}
	if ( ! is_user_logged_in() ) {
		return wp_login_url( get_permalink( $post ) );
	}
	if ( get_current_user_id() === $uid ) {
		return '';
	}
	$user = get_userdata( $uid );
	if ( ! $user ) {
		return '';
	}
	return trailingslashit( bp_loggedin_user_domain() ) . 'messages/compose/?r=' . rawurlencode( $user->user_nicename );
}

/**
 * Listing photo HTML: featured image, else the owner's avatar.
 */
function awd_photo( $post, $size = 'large', $class = '' ) {
	if ( has_post_thumbnail( $post ) ) {
		return get_the_post_thumbnail( $post, $size, array( 'class' => $class, 'alt' => esc_attr( get_the_title( $post ) ) ) );
	}
	$uid = (int) $post->post_author;
	if ( $uid && function_exists( 'bp_core_fetch_avatar' ) ) {
		return bp_core_fetch_avatar( array( 'item_id' => $uid, 'type' => 'full', 'width' => 400, 'height' => 400, 'class' => $class, 'html' => true, 'alt' => get_the_title( $post ) ) );
	}
	return get_avatar( $uid, 400, '', get_the_title( $post ), array( 'class' => $class ) );
}
