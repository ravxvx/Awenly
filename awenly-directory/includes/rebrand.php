<?php
/**
 * One-time clean-up of the old "LinkIn Events" brand left in the database after the move to Awenly Circle.
 * Runs once (option awd_rebrand_v1), replaces the brand name in posts, post meta, BuddyBoss activity and
 * groups, GamiPress texts and Rank Math settings, and points old linkinevents.net links to awenlycircle.com.
 * E-mail addresses (e.g. info@linkinevents.net used for sending mail) and file names are never changed.
 * A summary is stored in the option awd_rebrand_log.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AWD_REBRAND_VERSION = 2;

/** Replace the old brand in a string. Leaves e-mail addresses, file names and slugs (linkin-events-...) alone. */
function awd_rebrand_text( $s ) {
	if ( ! is_string( $s ) || false === stripos( $s, 'linkin' ) ) {
		return $s;
	}
	$home = untrailingslashit( home_url() );
	// old site links -> new domain (not e-mail addresses)
	$s = preg_replace( '~(?<![@\w.-])https?://(?:www\.)?linkinevents\.net~i', $home, $s );
	$s = preg_replace( '~(?<![@\w.-])https?://(?:www\.)?linkinevents\.ne(?=[\'"\s<]|$)~i', $home, $s ); // broken links missing the "t"
	$s = preg_replace( '~(?<![@\w.-])https?:\\\\/\\\\/(?:www\.)?linkinevents\.net~i', str_replace( '/', '\\/', $home ), $s ); // JSON-escaped (Elementor)
	// "LinkIn Events Help & Support" and other brand mentions
	$s = preg_replace( '~(?<![@/\w.-])(?:www\.)?LinkIn\s?Events\.net(?![\w-])~i', 'Awenly Circle', $s );
	$s = preg_replace( '~(?<![@/\w.-])LinkIn ?Events(?![\w-]|\.(?:net|com|png|jpe?g|webp|gif|svg)\b)~i', 'Awenly Circle', $s );
	return $s;
}

function awd_rebrand_deep( $v ) {
	if ( is_string( $v ) ) {
		return awd_rebrand_text( $v );
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			$v[ $k ] = awd_rebrand_deep( $x );
		}
	}
	return $v;
}

function awd_rebrand_run() {
	global $wpdb;
	$log = array( 'posts' => 0, 'meta' => 0, 'activity' => 0, 'groups' => 0, 'forums' => 0, 'terms' => 0, 'users' => 0, 'options' => 0 );

	// posts (all types, incl. pages, blog posts, GamiPress badges/levels/requirements, forums, events)
	$rows = $wpdb->get_results( "SELECT ID, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_type NOT IN ('revision') AND (post_title LIKE '%linkin%' OR post_content LIKE '%linkin%' OR post_excerpt LIKE '%linkin%')" );
	foreach ( $rows as $r ) {
		$new = array( 'post_title' => awd_rebrand_text( $r->post_title ), 'post_content' => awd_rebrand_text( $r->post_content ), 'post_excerpt' => awd_rebrand_text( $r->post_excerpt ) );
		if ( $new['post_title'] !== $r->post_title || $new['post_content'] !== $r->post_content || $new['post_excerpt'] !== $r->post_excerpt ) {
			$wpdb->update( $wpdb->posts, $new, array( 'ID' => $r->ID ) );
			clean_post_cache( (int) $r->ID );
			$log['posts']++;
		}
	}

	// post meta (Elementor data, image alt text, Rank Math descriptions, GamiPress texts ...)
	$rows = $wpdb->get_results( "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE '%linkin%' AND meta_key NOT IN ('_wp_attached_file','_wp_attachment_metadata','_wp_old_slug') LIMIT 5000" );
	foreach ( $rows as $r ) {
		if ( is_serialized( $r->meta_value ) ) {
			$val = maybe_unserialize( $r->meta_value );
			if ( is_object( $val ) ) {
				continue;
			}
			$new = maybe_serialize( awd_rebrand_deep( $val ) );
		} else {
			$new = awd_rebrand_text( $r->meta_value );
		}
		if ( $new !== $r->meta_value ) {
			$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $new ), array( 'meta_id' => $r->meta_id ) );
			wp_cache_delete( (int) $r->post_id, 'post_meta' );
			$log['meta']++;
		}
	}

	// BuddyBoss activity feed
	$t = $wpdb->prefix . 'bp_activity';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) === $t ) {
		$rows = $wpdb->get_results( "SELECT id, action, content FROM {$t} WHERE action LIKE '%linkin%' OR content LIKE '%linkin%'" );
		foreach ( $rows as $r ) {
			$a = awd_rebrand_text( $r->action );
			$c = awd_rebrand_text( $r->content );
			if ( $a !== $r->action || $c !== $r->content ) {
				$wpdb->update( $t, array( 'action' => $a, 'content' => $c ), array( 'id' => $r->id ) );
				$log['activity']++;
			}
		}
		$tm = $wpdb->prefix . 'bp_activity_meta';
		$rows = $wpdb->get_results( "SELECT id, meta_value FROM {$tm} WHERE meta_value LIKE '%linkin%' LIMIT 5000" );
		foreach ( $rows as $r ) {
			$val = maybe_unserialize( $r->meta_value );
			if ( is_object( $val ) ) {
				continue;
			}
			$new = is_serialized( $r->meta_value ) ? maybe_serialize( awd_rebrand_deep( $val ) ) : awd_rebrand_text( $r->meta_value );
			if ( $new !== $r->meta_value ) {
				$wpdb->update( $tm, array( 'meta_value' => $new ), array( 'id' => $r->id ) );
				$log['activity']++;
			}
		}
	}

	// BuddyBoss groups: name, description and the old "linkin-events-..." slug (also its forum)
	$t = $wpdb->prefix . 'bp_groups';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) === $t ) {
		$rows = $wpdb->get_results( "SELECT id, name, slug, description FROM {$t} WHERE name LIKE '%linkin%' OR slug LIKE '%linkin%' OR description LIKE '%linkin%'" );
		foreach ( $rows as $r ) {
			$name = awd_rebrand_text( $r->name );
			$slug = str_ireplace( 'linkin-events', 'awenly-circle', $r->slug );
			$wpdb->update( $t, array( 'name' => $name, 'slug' => $slug, 'description' => awd_rebrand_text( $r->description ) ), array( 'id' => $r->id ) );
			$log['groups']++;
		}
	}
	$forums = $wpdb->get_results( "SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = 'forum' AND post_name LIKE '%linkin-events%'" );
	foreach ( $forums as $f ) {
		$wpdb->update( $wpdb->posts, array( 'post_name' => str_ireplace( 'linkin-events', 'awenly-circle', $f->post_name ) ), array( 'ID' => $f->ID ) );
		add_post_meta( (int) $f->ID, '_wp_old_slug', $f->post_name );
		clean_post_cache( (int) $f->ID );
		$log['forums']++;
	}

	// category / tag names and descriptions
	$rows = $wpdb->get_results( "SELECT t.term_id, t.name, tt.term_taxonomy_id, tt.description FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE t.name LIKE '%linkin%' OR tt.description LIKE '%linkin%'" );
	foreach ( $rows as $r ) {
		$wpdb->update( $wpdb->terms, array( 'name' => awd_rebrand_text( $r->name ) ), array( 'term_id' => $r->term_id ) );
		$wpdb->update( $wpdb->term_taxonomy, array( 'description' => awd_rebrand_text( $r->description ) ), array( 'term_taxonomy_id' => $r->term_taxonomy_id ) );
		clean_term_cache( (int) $r->term_id );
		$log['terms']++;
	}

	// user display names like "LinkInEvents.net" (login and profile URL stay the same)
	$rows = $wpdb->get_results( "SELECT ID, display_name FROM {$wpdb->users} WHERE display_name LIKE '%linkin%'" );
	foreach ( $rows as $r ) {
		$new = awd_rebrand_text( $r->display_name );
		if ( $new !== $r->display_name ) {
			$wpdb->update( $wpdb->users, array( 'display_name' => $new ), array( 'ID' => $r->ID ) );
			clean_user_cache( (int) $r->ID );
			$log['users']++;
		}
	}

	// Rank Math (organisation name in the schema / knowledge graph), site title and tagline
	foreach ( array( 'rank-math-options-titles', 'rank-math-options-general', 'blogname', 'blogdescription', 'buddyboss_theme_options' ) as $opt ) {
		$val = get_option( $opt );
		if ( false === $val ) {
			continue;
		}
		$new = awd_rebrand_deep( $val );
		if ( 'rank-math-options-titles' === $opt && is_array( $new ) ) {
			if ( empty( $new['knowledgegraph_name'] ) || false !== stripos( (string) $new['knowledgegraph_name'], 'linkin' ) ) {
				$new['knowledgegraph_name'] = 'Awenly Circle';
			}
			if ( empty( $new['website_name'] ) || false !== stripos( (string) $new['website_name'], 'linkin' ) ) {
				$new['website_name'] = 'Awenly Circle';
			}
		}
		if ( $new !== $val ) {
			update_option( $opt, $new );
			$log['options']++;
		}
	}

	// event directory page: Rank Math description written for the old EventON page
	$p = get_page_by_path( 'event-directory' );
	if ( $p ) {
		update_post_meta( $p->ID, 'rank_math_description', 'Upcoming workshops, circles, retreats and online sessions from the Awenly Circle community. Adding your event is free.' );
	}

	// clear caches so visitors see the new texts
	wp_cache_flush();
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
	do_action( 'litespeed_purge_all' );

	$log['time'] = gmdate( 'c' );
	update_option( 'awd_rebrand_log', $log, false );
	return $log;
}

add_action( 'init', function () {
	if ( (int) get_option( 'awd_rebrand_v', 0 ) >= AWD_REBRAND_VERSION || get_transient( 'awd_rebrand_lock' ) ) {
		return;
	}
	set_transient( 'awd_rebrand_lock', 1, 10 * MINUTE_IN_SECONDS );
	update_option( 'awd_rebrand_v', AWD_REBRAND_VERSION, true ); // mark first, so a failure can never loop
	awd_rebrand_run();
	delete_transient( 'awd_rebrand_lock' );
}, 99 );

/* Rank Math schema: never publish the old brand or the old Facebook page as the organisation. */
add_filter( 'rank_math/json_ld', function ( $data ) {
	return awd_rebrand_deep( $data );
}, 999 );

/* Admin notice with the summary (visible once to admins). */
add_action( 'admin_notices', function () {
	$log = get_option( 'awd_rebrand_log' );
	if ( ! $log || get_option( 'awd_rebrand_seen' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	update_option( 'awd_rebrand_seen', 1 );
	printf( '<div class="notice notice-success is-dismissible"><p><strong>Awenly Circle:</strong> old "LinkIn Events" texts were replaced - %d posts/pages, %d settings fields, %d activity entries, %d groups, %d forums, %d categories, %d users, %d options.</p></div>',
		(int) $log['posts'], (int) $log['meta'], (int) $log['activity'], (int) $log['groups'], (int) $log['forums'], (int) $log['terms'], (int) $log['users'], (int) $log['options'] );
} );
