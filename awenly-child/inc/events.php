<?php
/**
 * Event helpers for the homepage.
 *
 * Works with the "awenly_event" post type from the Awenly Circle plugin
 * (both the Core and the All-in-One versions, which store the start date
 * under different meta keys). Adds Location + End time fields to events.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', function () {
	if ( ! post_type_exists( 'awenly_event' ) ) {
		return;
	}
	add_meta_box( 'awenly-event-extra', __( 'Event location & end time (Awenly theme)', 'awenly-child' ), function ( $post ) {
		wp_nonce_field( 'awenly_event_extra', 'awenly_event_extra_nonce' );
		$location = get_post_meta( $post->ID, '_awenly_event_location', true );
		$end      = get_post_meta( $post->ID, '_awenly_event_end', true );
		echo '<p><label>' . esc_html__( 'Location (e.g. Cork, Ireland)', 'awenly-child' ) . '<br><input class="widefat" name="awenly_event_location" value="' . esc_attr( $location ) . '"></label></p>';
		echo '<p><label>' . esc_html__( 'End time', 'awenly-child' ) . '<br><input type="time" name="awenly_event_end" value="' . esc_attr( $end ) . '"></label></p>';
	}, 'awenly_event', 'side' );
} );

add_action( 'save_post_awenly_event', function ( $post_id ) {
	if ( ! isset( $_POST['awenly_event_extra_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awenly_event_extra_nonce'] ) ), 'awenly_event_extra' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['awenly_event_location'] ) ) {
		update_post_meta( $post_id, '_awenly_event_location', sanitize_text_field( wp_unslash( $_POST['awenly_event_location'] ) ) );
	}
	if ( isset( $_POST['awenly_event_end'] ) ) {
		update_post_meta( $post_id, '_awenly_event_end', sanitize_text_field( wp_unslash( $_POST['awenly_event_end'] ) ) );
	}
} );

/**
 * Start timestamp of an event (site timezone), or 0 when not set.
 */
function awenly_event_start_ts( $post_id ) {
	if ( 'tribe_events' === get_post_type( $post_id ) && function_exists( 'tribe_get_start_date' ) ) {
		return (int) tribe_get_start_date( $post_id, false, 'U' );
	}
	foreach ( array( '_aw_event_start', '_awenly_event_start' ) as $key ) {
		$raw = get_post_meta( $post_id, $key, true );
		if ( $raw ) {
			try {
				$dt = new DateTime( $raw, wp_timezone() );
				return $dt->getTimestamp();
			} catch ( Exception $e ) {
				continue;
			}
		}
	}
	return 0;
}

/**
 * Upcoming events, soonest first. Events without a date come last.
 *
 * @return WP_Post[]
 */
function awenly_upcoming_events( $limit = 3 ) {
	// The Events Calendar takes priority when it is active.
	if ( function_exists( 'tribe_get_events' ) ) {
		$tec = tribe_get_events( array(
			'posts_per_page' => max( 1, (int) $limit ),
			'start_date'     => 'now',
			'eventDisplay'   => 'list',
		) );
		if ( $tec ) {
			return $tec;
		}
	}
	if ( ! post_type_exists( 'awenly_event' ) ) {
		return array();
	}
	$posts = get_posts( array(
		'post_type'      => 'awenly_event',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'no_found_rows'  => true,
	) );
	$now      = time() - DAY_IN_SECONDS / 2;
	$dated    = array();
	$undated  = array();
	foreach ( $posts as $p ) {
		$ts = awenly_event_start_ts( $p->ID );
		if ( $ts && $ts >= $now ) {
			$dated[] = array( $ts, $p );
		} elseif ( ! $ts ) {
			$undated[] = $p;
		}
	}
	usort( $dated, function ( $a, $b ) {
		return $a[0] <=> $b[0];
	} );
	$out = array_merge( wp_list_pluck( $dated, 1 ), $undated );
	return array_slice( $out, 0, max( 1, (int) $limit ) );
}

/**
 * Link for an event card: booking URL if set, otherwise the event page.
 */
function awenly_event_link( $post_id ) {
	$url = get_post_meta( $post_id, '_aw_event_url', true );
	return $url ? $url : get_permalink( $post_id );
}


/**
 * Location text for an event (TEC venue city, or the Awenly meta field).
 */
function awenly_event_location( $post_id ) {
	if ( 'tribe_events' === get_post_type( $post_id ) && function_exists( 'tribe_get_venue' ) ) {
		$parts = array_filter( array( tribe_get_venue( $post_id ), function_exists( 'tribe_get_city' ) ? tribe_get_city( $post_id ) : '' ) );
		if ( $parts ) {
			return implode( ', ', array_unique( $parts ) );
		}
		if ( function_exists( 'tribe_is_virtual_event' ) && tribe_is_virtual_event( $post_id ) ) {
			return __( 'Online', 'awenly-child' );
		}
	}
	return (string) get_post_meta( $post_id, '_awenly_event_location', true );
}

/**
 * End time (H:i) for an event.
 */
function awenly_event_end( $post_id ) {
	if ( 'tribe_events' === get_post_type( $post_id ) && function_exists( 'tribe_get_end_date' ) ) {
		return (string) tribe_get_end_date( $post_id, false, 'H:i' );
	}
	return (string) get_post_meta( $post_id, '_awenly_event_end', true );
}
