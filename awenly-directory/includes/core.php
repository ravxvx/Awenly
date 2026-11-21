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
/**
 * Listing types. Stored in post meta "_awd_type"; empty = practitioner.
 */
function awd_types() {
	return array(
		'practitioner' => array( 'label' => __( 'Practitioner', 'awenly-directory' ), 'plural' => __( 'Practitioners', 'awenly-directory' ), 'icon' => 'user', 'desc' => __( 'You offer sessions, therapy, coaching or classes.', 'awenly-directory' ) ),
		'centre'       => array( 'label' => __( 'Centre / Studio', 'awenly-directory' ), 'plural' => __( 'Centres & Studios', 'awenly-directory' ), 'icon' => 'home', 'desc' => __( 'A clinic, yoga studio or wellness centre with a team.', 'awenly-directory' ) ),
		'retreat'      => array( 'label' => __( 'Retreat', 'awenly-directory' ), 'plural' => __( 'Retreats', 'awenly-directory' ), 'icon' => 'sun', 'desc' => __( 'A retreat or wellbeing trip with dates and a place.', 'awenly-directory' ) ),
		'venue'        => array( 'label' => __( 'Venue', 'awenly-directory' ), 'plural' => __( 'Venues', 'awenly-directory' ), 'icon' => 'door', 'desc' => __( 'A room or space to rent for workshops and circles.', 'awenly-directory' ) ),
		'shop'         => array( 'label' => __( 'Shop', 'awenly-directory' ), 'plural' => __( 'Shops & Products', 'awenly-directory' ), 'icon' => 'bag', 'desc' => __( 'Crystals, books, natural products, handmade crafts.', 'awenly-directory' ) ),
		'course'       => array( 'label' => __( 'Course', 'awenly-directory' ), 'plural' => __( 'Courses', 'awenly-directory' ), 'icon' => 'book', 'desc' => __( 'An online or in-person course or training.', 'awenly-directory' ) ),
	);
}

function awd_type( $post ) {
	$id = is_object( $post ) ? $post->ID : (int) $post;
	$t  = (string) get_post_meta( $id, '_awd_type', true );
	return array_key_exists( $t, awd_types() ) ? $t : 'practitioner';
}

/**
 * Listing fields. "types" limits a field to some listing types (omitted = all).
 */
function awd_fields() {
	return array(
		'tagline'        => array( 'label' => __( 'Short tagline', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Reiki Master & Sound Healer', 'awenly-directory' ), 'max' => 90 ),
		'location'       => array( 'label' => __( 'City / area', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Cork, Ireland', 'awenly-directory' ), 'max' => 80 ),
		'mode'           => array( 'label' => __( 'Format', 'awenly-directory' ), 'type' => 'select', 'options' => array( 'Online' => __( 'Online', 'awenly-directory' ), 'In person' => __( 'In person', 'awenly-directory' ), 'Both' => __( 'Online & in person', 'awenly-directory' ) ), 'types' => array( 'practitioner', 'centre', 'course', 'shop' ) ),
		'date_start'     => array( 'label' => __( 'Starts on', 'awenly-directory' ), 'type' => 'date', 'types' => array( 'retreat', 'course' ) ),
		'date_end'       => array( 'label' => __( 'Ends on', 'awenly-directory' ), 'type' => 'date', 'types' => array( 'retreat', 'course' ) ),
		'fee'            => array( 'label' => __( 'Price from', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. €60 / session', 'awenly-directory' ), 'max' => 40 ),
		'capacity'       => array( 'label' => __( 'Capacity', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. up to 25 people', 'awenly-directory' ), 'max' => 40, 'types' => array( 'venue', 'retreat', 'centre' ) ),
		'hours'          => array( 'label' => __( 'Opening hours', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Mon–Sat 9:00–19:00', 'awenly-directory' ), 'max' => 80, 'types' => array( 'centre', 'shop', 'venue' ) ),
		'languages'      => array( 'label' => __( 'Languages', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. English, Polish', 'awenly-directory' ), 'max' => 80, 'types' => array( 'practitioner', 'centre', 'retreat', 'course' ) ),
		'qualifications' => array( 'label' => __( 'Training & qualifications', 'awenly-directory' ), 'type' => 'text', 'placeholder' => __( 'e.g. Usui Reiki III, ICF coach', 'awenly-directory' ), 'max' => 160, 'types' => array( 'practitioner', 'centre', 'course', 'retreat' ) ),
		'website'        => array( 'label' => __( 'Website / shop link', 'awenly-directory' ), 'type' => 'url', 'placeholder' => 'https://' ),
		'booking_url'    => array( 'label' => __( 'Booking / tickets link', 'awenly-directory' ), 'type' => 'url', 'placeholder' => 'https://', 'types' => array( 'practitioner', 'centre', 'retreat', 'venue', 'course' ) ),
	);
}

function awd_field_applies( $field, $type ) {
	return empty( $field['types'] ) || in_array( $type, $field['types'], true );
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
		'psychology'  => 'Psychology & Psychotherapy',
		'nutrition'   => 'Nutrition & Dietetics',
		'naturopathy' => 'Naturopathy & Herbalism',
		'massage'     => 'Massage & Bodywork',
		'physio'      => 'Physiotherapy & Osteopathy',
		'fitness'     => 'Fitness & Personal Training',
		'dance'       => 'Dance & Pilates',
		'life-coach'  => 'Life & Business Coaching',
		'birth'       => 'Pregnancy, Birth & Parenting',
		'creative'    => 'Art & Music Therapy',
		'astrology'   => 'Astrology & Tarot',
		'products'    => 'Natural Products & Crafts',
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
				'singular_name' => __( 'Listing', 'awenly-directory' ),
				'add_new_item'  => __( 'Add Listing', 'awenly-directory' ),
				'edit_item'     => __( 'Edit Listing', 'awenly-directory' ),
				'all_items'     => __( 'All Listings', 'awenly-directory' ),
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
			'labels'       => array( 'name' => __( 'Categories', 'awenly-directory' ), 'singular_name' => __( 'Category', 'awenly-directory' ) ),
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
function awd_get_user_listings( $user_id, $published_only = false ) {
	if ( ! $user_id ) {
		return array();
	}
	return get_posts( array(
		'post_type'      => AWD_CPT,
		'author'         => (int) $user_id,
		'post_status'    => $published_only ? 'publish' : array( 'publish', 'pending', 'draft' ),
		'posts_per_page' => 20,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
}

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
