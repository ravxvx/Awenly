<?php
/**
 * One-time SEO clean-up from the November 2026 audit (runs once, option awd_seo_fix_v).
 * - retires off-topic blog posts and old business events (draft + 301 to a relevant page)
 * - noindex for account / shop / affiliate / thank-you pages
 * - titles and descriptions for the key pages, Rank Math templates for listings and events
 * - sitemap: listings and events in, GamiPress items and forum replies out
 * - schema: Awenly logo, About page, no Article schema on pages
 * Every changed value is saved first in the option awd_seo_fix_backup.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AWD_SEO_FIX_VERSION = 1;

function awd_seo_retire_ids() {
	return array( 10321, 10264, 6762, 6669, 6646, 6666, 6657, 6648, 6501, 6504, 6507, 6509, 6511, 6514, 6498, 6515, 6497, 6493, 6518, 6520, 6521, 6523, 6491, 6489, 6360, 6340, 6335, 6329, 6315, 5735, 5726, 5715, 5500, 5296, 5284, 5279, 5274, 5255, 5250, 5244, 5239, 5213, 5211, 4614, 3669, 3060, 3056, 3050, 3000, 2339, 2211, 1933, 1737, 6485, 5235, 5232, 1069, 1078, 1072, 1075 );
}

function awd_seo_fix_run() {
	$backup  = array( 'posts' => array(), 'meta' => array(), 'options' => array() );
	$retired = (array) get_option( 'awd_retired_urls', array() );
	$blog    = home_url( '/category/health/' );

	// 1. retire off-topic posts / old business events, and leftover pages
	$pages_retire = array( 'home' => home_url( '/' ), 'sample-page' => home_url( '/' ), 'elementor-5318' => home_url( '/' ), 'google-seo-score' => home_url( '/about/' ) );
	$targets      = array();
	foreach ( awd_seo_retire_ids() as $id ) {
		$targets[ $id ] = 'tribe_events' === get_post_type( $id ) ? home_url( '/events/' ) : $blog;
	}
	foreach ( $pages_retire as $slug => $to ) {
		$p = get_page_by_path( $slug );
		if ( $p && (int) get_option( 'page_on_front' ) !== (int) $p->ID ) {
			$targets[ $p->ID ] = $to;
		}
	}
	foreach ( $targets as $id => $to ) {
		$post = get_post( $id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			continue;
		}
		$path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
		$backup['posts'][ $id ] = $post->post_status;
		wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		if ( $path ) {
			$retired[ untrailingslashit( $path ) ] = $to;
		}
	}
	update_option( 'awd_retired_urls', $retired, true );

	// 2. noindex for utility pages
	$noindex = array( 'account', 'account-2', 'login', 'login-2', 'activate', 'cart', 'checkout', 'my-account', 'shop', 'thank-you', 'thank-you-2', 'tickets-checkout', 'tickets-order', 'bookmarks', 'moderation', 'documents', 'photos', 'referral', 'referral-system', 'affiliate-area', 'affiliate-dashboard', 'affiliate-login', 'affiliate-signup', 'groups-dashboard', 'my-events', 'add-events', 'create-a-blog' );
	foreach ( $noindex as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$backup['meta'][ $p->ID ]['rank_math_robots'] = get_post_meta( $p->ID, 'rank_math_robots', true );
			update_post_meta( $p->ID, 'rank_math_robots', array( 'noindex', 'follow' ) );
		}
	}

	// 3. titles and descriptions of the key pages
	$front = (int) get_option( 'page_on_front' );
	$seo   = array(
		$front            => array( 'Awenly Circle – Holistic Practitioners, Healing Events & Community', 'Find trusted holistic practitioners, join healing circles, workshops and retreats, and connect with a community for personal growth and wellbeing.', 'holistic practitioners' ),
		'about'           => array( 'About Awenly Circle – A Home for Holistic Wellbeing', 'Awenly Circle brings together people who care about mind, body and spirit and the practitioners who support them. Free to join, list and take part.', 'holistic wellbeing community' ),
		'contact-us'      => array( 'Contact Awenly Circle', 'Questions about membership, practitioner listings, events or partnerships? Send the Awenly Circle team a message and we will get back to you.', '' ),
		'news-feed'       => array( 'Community Feed – Awenly Circle', 'See what members of the Awenly Circle community share: experiences, recommendations, events and support on the path of healing and growth.', 'wellbeing community' ),
		'blog'            => array( 'Articles on Healing, Wellbeing & Spirituality – Awenly Circle', 'Articles from practitioners and members about holistic health, healing, personal growth and spirituality. Write and share your own posts too.', 'wellbeing articles' ),
		'groups'          => array( 'Community Groups – Awenly Circle', 'Join groups around the practices you love: meditation, energy healing, yoga, nutrition, spirituality and more. Meet people on the same path.', '' ),
		'register'        => array( 'Join Awenly Circle – Free Membership', 'Create your free Awenly Circle account to join the community, find practitioners, save listings, add events and list your own practice.', '' ),
		'event-directory' => array( 'Wellbeing Events, Workshops & Retreats – Awenly Circle', 'Upcoming workshops, circles, retreats and online sessions from the Awenly Circle community. Adding your event is free.', 'wellbeing events' ),
		'add-events'      => array( 'Add Your Event – Awenly Circle', 'Add your workshop, healing circle, retreat or online session to the Awenly Circle events calendar for free.', '' ),
	);
	foreach ( $seo as $key => $v ) {
		$p = is_int( $key ) ? get_post( $key ) : get_page_by_path( $key );
		if ( ! $p ) {
			continue;
		}
		foreach ( array( 'rank_math_title' => $v[0], 'rank_math_description' => $v[1], 'rank_math_focus_keyword' => $v[2] ) as $mk => $mv ) {
			if ( '' === $mv ) {
				continue;
			}
			$backup['meta'][ $p->ID ][ $mk ] = get_post_meta( $p->ID, $mk, true );
			update_post_meta( $p->ID, $mk, $mv );
		}
	}

	// 4. posts that are kept: a description when missing; keyword for the rosacea guide
	$kept = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 300, 'fields' => 'ids' ) );
	foreach ( $kept as $id ) {
		if ( get_post_meta( $id, 'rank_math_description', true ) ) {
			continue;
		}
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ) ) );
		if ( mb_strlen( $text ) < 60 ) {
			continue;
		}
		$desc = mb_substr( $text, 0, 155 );
		$desc = mb_substr( $desc, 0, (int) mb_strrpos( $desc, ' ' ) ) . '…';
		$backup['meta'][ $id ]['rank_math_description'] = '';
		update_post_meta( $id, 'rank_math_description', $desc );
	}
	$ros = get_page_by_path( 'soothe-your-skin-a-guide-to-treating-rosacea-with-organic-products', OBJECT, 'post' );
	if ( $ros && ! get_post_meta( $ros->ID, 'rank_math_focus_keyword', true ) ) {
		update_post_meta( $ros->ID, 'rank_math_focus_keyword', 'rosacea' );
	}

	// 5. Rank Math settings
	$sitemap = (array) get_option( 'rank-math-options-sitemap', array() );
	$titles  = (array) get_option( 'rank-math-options-titles', array() );
	$backup['options']['rank-math-options-sitemap'] = $sitemap;
	$backup['options']['rank-math-options-titles']  = $titles;
	$sitemap['pt_' . AWD_CPT . '_sitemap'] = 'on';
	$sitemap['pt_tribe_events_sitemap']    = 'on';
	foreach ( array( 'badges', 'quests', 'grade', 'level', 'reply', 'ajde_events', 'memberpressproduct', 'memberpressgroup', 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'ld-exam', 'sfwd-certificates', 'sfwd-essays', 'sfwd-assignment', 'groups', 'newsletter', 'e-landing-page' ) as $pt ) {
		$sitemap[ 'pt_' . $pt . '_sitemap' ] = 'off';
	}
	update_option( 'rank-math-options-sitemap', $sitemap );

	foreach ( array( 'badges', 'quests', 'grade', 'level', 'reply' ) as $pt ) {
		$titles[ 'pt_' . $pt . '_custom_robots' ] = 'on';
		$titles[ 'pt_' . $pt . '_robots' ]        = array( 'noindex' );
	}
	$titles[ 'pt_' . AWD_CPT . '_title' ]               = '%title% %sep% %sitename%';
	$titles[ 'pt_' . AWD_CPT . '_description' ]         = '%excerpt%';
	$titles[ 'pt_' . AWD_CPT . '_custom_robots' ]       = 'on';
	$titles[ 'pt_' . AWD_CPT . '_robots' ]              = array( 'index' );
	$titles[ 'pt_' . AWD_CPT . '_default_rich_snippet' ] = 'off';
	$titles[ 'pt_' . AWD_CPT . '_archive_title' ]       = 'Find Holistic Practitioners, Therapists & Healers %sep% %sitename%';
	$titles[ 'pt_' . AWD_CPT . '_archive_description' ] = 'Search holistic and wellbeing practitioners by therapy, town or online sessions. Read reviews and contact therapists, coaches and healers directly.';
	$titles['pt_tribe_events_title']               = '%title% %sep% Events %sep% %sitename%';
	$titles['pt_tribe_events_description']         = '%excerpt%';
	$titles['pt_tribe_events_custom_robots']       = 'on';
	$titles['pt_tribe_events_robots']              = array( 'index' );
	$titles['pt_tribe_events_archive_title']       = 'Wellbeing Events, Workshops & Retreats %sep% %sitename%';
	$titles['pt_tribe_events_archive_description'] = 'Healing circles, workshops, retreats and online sessions hosted by the Awenly Circle community. Find an event near you or add your own for free.';
	$titles['pt_page_default_rich_snippet']        = 'off';
	$titles['local_seo_about_page']                = (string) ( get_page_by_path( 'about' ) ? get_page_by_path( 'about' )->ID : '' );
	$titles['maps_api_key']                        = '';
	$titles['homepage_title']                      = 'Awenly Circle – Holistic Practitioners, Healing Events & Community';
	$titles['homepage_description']                = 'Find trusted holistic practitioners, join healing circles, workshops and retreats, and connect with a community for personal growth and wellbeing.';
	$icon = (int) get_option( 'site_icon' );
	if ( $icon && wp_get_attachment_url( $icon ) ) {
		$titles['knowledgegraph_logo']    = wp_get_attachment_url( $icon );
		$titles['knowledgegraph_logo_id'] = (string) $icon;
	}
	update_option( 'rank-math-options-titles', $titles );

	// 6. old LinkedIn admin link in the theme options -> public company page
	$bb = get_option( 'buddyboss_theme_options' );
	if ( is_array( $bb ) ) {
		$backup['options']['buddyboss_theme_options'] = $bb;
		array_walk_recursive( $bb, function ( &$v ) {
			if ( is_string( $v ) && false !== strpos( $v, 'linkedin.com/company/' ) ) {
				$v = preg_replace( '~/admin/dashboard/?~', '/', $v );
			}
		} );
		update_option( 'buddyboss_theme_options', $bb );
	}

	update_option( 'awd_seo_fix_backup', $backup, false );

	// caches: Rank Math sitemaps, object cache, LiteSpeed
	if ( class_exists( '\RankMath\Sitemap\Cache' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	wp_cache_flush();
	do_action( 'litespeed_purge_all' );
	update_option( 'awd_seo_fix_log', array( 'retired' => count( $backup['posts'] ), 'time' => gmdate( 'c' ) ), false );
}

add_action( 'init', function () {
	if ( (int) get_option( 'awd_seo_fix_v', 0 ) >= AWD_SEO_FIX_VERSION || get_transient( 'awd_seo_fix_lock' ) ) {
		return;
	}
	set_transient( 'awd_seo_fix_lock', 1, 10 * MINUTE_IN_SECONDS );
	update_option( 'awd_seo_fix_v', AWD_SEO_FIX_VERSION, true );
	awd_seo_fix_run();
	delete_transient( 'awd_seo_fix_lock' );
}, 100 );

/* 301 for retired posts and pages (they are drafts now, so visitors would get a 404). */
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$map  = (array) get_option( 'awd_retired_urls', array() );
	$path = untrailingslashit( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) );
	if ( $path && isset( $map[ $path ] ) ) {
		wp_safe_redirect( $map[ $path ], 301 );
		exit;
	}
}, 1 );
