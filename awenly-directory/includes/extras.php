<?php
/**
 * Verified badge, activity post on approval, view counter, map (OpenStreetMap), practitioner events.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ===================== Verified badge ===================== */

function awd_is_verified( $post_id ) {
	return (bool) get_post_meta( $post_id, '_awd_verified', true );
}

function awd_verified_badge( $small = false ) {
	return '<span class="awd-verified' . ( $small ? ' awd-verified--sm' : '' ) . '" title="' . esc_attr__( 'Verified by Awenly Circle', 'awenly-directory' ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 1.8 14.6 4l3.4-.3.9 3.3 3 1.7-1.2 3.2 1.2 3.2-3 1.7-.9 3.3-3.4-.3L12 22.2 9.4 20l-3.4.3-.9-3.3-3-1.7 1.2-3.2L2.1 8.9l3-1.7L6 3.9l3.4.3z"/><path d="m7.8 12.2 2.7 2.7 5.7-5.8" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>' . ( $small ? '' : '<span>' . esc_html__( 'Verified', 'awenly-directory' ) . '</span>' ) . '</span>';
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'awd-verified', __( 'Awenly verification', 'awenly-directory' ), function ( $post ) {
		wp_nonce_field( 'awd_verified', 'awd_verified_nonce' );
		echo '<label><input type="checkbox" name="awd_verified" value="1" ' . checked( awd_is_verified( $post->ID ), true, false ) . '> ' . esc_html__( 'Verified (qualifications / business checked)', 'awenly-directory' ) . '</label>';
		$r   = awd_rating( $post->ID );
		$lat = get_post_meta( $post->ID, '_awd_lat', true );
		echo '<p>' . ( '' !== $lat ? esc_html__( 'Map: shown', 'awenly-directory' ) : esc_html__( 'Map: no location found yet', 'awenly-directory' ) . ( get_option( 'awd_geo_last_error' ) ? '<br><small>' . esc_html( get_option( 'awd_geo_last_error' ) ) . '</small>' : '' ) ) . '</p>';
		echo '<p style="margin-top:10px">' . esc_html( sprintf( __( 'Rating: %1$s (%2$d reviews) · Views: %3$d', 'awenly-directory' ), number_format_i18n( $r['avg'], 1 ), $r['count'], (int) get_post_meta( $post->ID, '_awd_views', true ) ) ) . '</p>';
	}, AWD_CPT, 'side', 'high' );
} );
add_action( 'save_post_' . AWD_CPT, function ( $post_id ) {
	if ( ! isset( $_POST['awd_verified_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_verified_nonce'] ) ), 'awd_verified' ) || ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	update_post_meta( $post_id, '_awd_verified', empty( $_POST['awd_verified'] ) ? 0 : 1 );
} );
add_filter( 'manage_' . AWD_CPT . '_posts_columns', function ( $cols ) {
	$cols['awd_verified'] = __( 'Verified', 'awenly-directory' );
	$cols['awd_rating']   = __( 'Rating', 'awenly-directory' );
	return $cols;
}, 20 );
add_action( 'manage_' . AWD_CPT . '_posts_custom_column', function ( $col, $post_id ) {
	if ( 'awd_verified' === $col ) {
		echo awd_is_verified( $post_id ) ? '✔' : '—';
	}
	if ( 'awd_rating' === $col ) {
		$r = awd_rating( $post_id );
		echo $r['count'] ? esc_html( number_format_i18n( $r['avg'], 1 ) . ' ★ (' . $r['count'] . ')' ) : '—';
	}
}, 10, 2 );

/* ===================== Defaults so sorting by rating includes everyone ===================== */

add_action( 'save_post_' . AWD_CPT, function ( $post_id ) {
	foreach ( array( '_awd_rating_avg' => 0, '_awd_rating_count' => 0, '_awd_verified' => 0, '_awd_views' => 0 ) as $k => $v ) {
		if ( '' === get_post_meta( $post_id, $k, true ) ) {
			update_post_meta( $post_id, $k, $v );
		}
	}
}, 5 );

/* ===================== Activity post on approval ===================== */

add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( AWD_CPT !== $post->post_type || 'publish' !== $new || 'publish' === $old || get_post_meta( $post->ID, '_awd_activity_posted', true ) ) {
		return;
	}
	if ( ! function_exists( 'bp_activity_add' ) || ! bp_is_active( 'activity' ) ) {
		return;
	}
	$uid = (int) $post->post_author;
	$id  = bp_activity_add( array(
		'user_id'      => $uid,
		'component'    => 'members',
		'type'         => 'awd_listing_published',
		'action'       => sprintf( __( '%1$s added a new listing to the Awenly Directory: %2$s', 'awenly-directory' ), bp_core_get_userlink( $uid ), '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $post->post_title ) . '</a>' ),
		'primary_link' => get_permalink( $post ),
		'item_id'      => $post->ID,
	) );
	if ( $id ) {
		update_post_meta( $post->ID, '_awd_activity_posted', $id );
	}
}, 20, 3 );

/* ===================== View counter ===================== */

add_action( 'template_redirect', function () {
	if ( ! is_singular( AWD_CPT ) || is_preview() ) {
		return;
	}
	$post = get_queried_object();
	if ( get_current_user_id() === (int) $post->post_author || current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) );
	if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|preview|monitor|headless/', $ua ) ) {
		return;
	}
	$cookie = 'awd_seen_' . $post->ID;
	if ( isset( $_COOKIE[ $cookie ] ) ) {
		return;
	}
	setcookie( $cookie, '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	update_post_meta( $post->ID, '_awd_views', (int) get_post_meta( $post->ID, '_awd_views', true ) + 1 );
}, 5 );

/* ===================== Map: geocoding (OpenStreetMap Nominatim) ===================== */

/**
 * Geocode a place name with free services (no API key): Nominatim → Open-Meteo → Photon.
 */
function awd_geocode( $place ) {
	$key    = 'awd_geo_' . md5( strtolower( $place ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$args = array( 'timeout' => 6, 'user-agent' => 'AwenlyCircle/1.1 (' . home_url() . '; ' . get_option( 'admin_email' ) . ')', 'headers' => array( 'Accept-Language' => 'en' ) );
	$try  = array(
		array( add_query_arg( array( 'format' => 'json', 'limit' => 1, 'q' => rawurlencode( $place ) ), 'https://nominatim.openstreetmap.org/search' ), function ( $d ) {
			return isset( $d[0]['lat'], $d[0]['lon'] ) ? array( (float) $d[0]['lat'], (float) $d[0]['lon'] ) : null;
		} ),
		array( add_query_arg( array( 'count' => 1, 'language' => 'en', 'name' => rawurlencode( trim( explode( ',', $place )[0] ) ) ), 'https://geocoding-api.open-meteo.com/v1/search' ), function ( $d ) {
			return isset( $d['results'][0]['latitude'] ) ? array( (float) $d['results'][0]['latitude'], (float) $d['results'][0]['longitude'] ) : null;
		} ),
		array( add_query_arg( array( 'limit' => 1, 'q' => rawurlencode( $place ) ), 'https://photon.komoot.io/api/' ), function ( $d ) {
			return isset( $d['features'][0]['geometry']['coordinates'] ) ? array( (float) $d['features'][0]['geometry']['coordinates'][1], (float) $d['features'][0]['geometry']['coordinates'][0] ) : null;
		} ),
	);
	$errors = array();
	foreach ( $try as $t ) {
		$res = wp_remote_get( $t[0], $args );
		if ( is_wp_error( $res ) ) {
			$errors[] = $res->get_error_message();
			continue;
		}
		$code = wp_remote_retrieve_response_code( $res );
		if ( 200 !== (int) $code ) {
			$errors[] = wp_parse_url( $t[0], PHP_URL_HOST ) . ' HTTP ' . $code;
			continue;
		}
		$c = call_user_func( $t[1], json_decode( wp_remote_retrieve_body( $res ), true ) );
		if ( $c ) {
			set_transient( $key, $c, 30 * DAY_IN_SECONDS );
			return $c;
		}
		$errors[] = wp_parse_url( $t[0], PHP_URL_HOST ) . ' no result';
	}
	update_option( 'awd_geo_last_error', gmdate( 'Y-m-d H:i' ) . ' "' . $place . '": ' . implode( ' | ', $errors ), false );
	return null;
}

function awd_geocode_listing( $post_id ) {
	$loc  = trim( awd_meta( $post_id, 'location' ) );
	$mode = awd_meta( $post_id, 'mode' );
	if ( '' === $loc ) {
		delete_post_meta( $post_id, '_awd_lat' );
		delete_post_meta( $post_id, '_awd_lng' );
		update_post_meta( $post_id, '_awd_geo_for', $loc . '|' . $mode );
		return;
	}
	if ( get_post_meta( $post_id, '_awd_geo_for', true ) === $loc . '|' . $mode ) {
		return; // unchanged
	}
	$fail = (int) get_post_meta( $post_id, '_awd_geo_fail', true );
	if ( $fail && time() - $fail < 6 * HOUR_IN_SECONDS ) {
		return; // recently failed, try again later
	}
	$coords = awd_geocode( $loc );
	if ( ! $coords ) {
		update_post_meta( $post_id, '_awd_geo_fail', time() );
		return;
	}
	delete_post_meta( $post_id, '_awd_geo_fail' );
	update_post_meta( $post_id, '_awd_lat', $coords[0] );
	update_post_meta( $post_id, '_awd_lng', $coords[1] );
	update_post_meta( $post_id, '_awd_geo_for', $loc . '|' . $mode );
}
add_action( 'awd_listing_saved', function ( $post_id ) {
	delete_post_meta( $post_id, '_awd_geo_fail' );
	awd_geocode_listing( $post_id );
} );
add_action( 'save_post_' . AWD_CPT, function ( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	delete_post_meta( $post_id, '_awd_geo_fail' );
	awd_geocode_listing( $post_id );
}, 99 );

function awd_map_points( $post_ids ) {
	$out    = array();
	$budget = 3; // geocode at most a few older listings per page view
	foreach ( $post_ids as $id ) {
		$loc = trim( awd_meta( $id, 'location' ) );
		if ( $budget > 0 && '' !== $loc && get_post_meta( $id, '_awd_geo_for', true ) !== $loc . '|' . awd_meta( $id, 'mode' ) ) {
			awd_geocode_listing( $id );
			$budget--;
		}
		$lat = get_post_meta( $id, '_awd_lat', true );
		$lng = get_post_meta( $id, '_awd_lng', true );
		if ( '' === $lat || '' === $lng ) {
			if ( '' !== $loc ) { // let the browser geocode it
				$out[] = array(
					'q'     => $loc,
					'title' => get_the_title( $id ),
					'place' => $loc . ( 'Online' === awd_meta( $id, 'mode' ) ? ' · ' . __( 'sessions online', 'awenly-directory' ) : '' ),
					'url'   => get_permalink( $id ),
				);
			}
			continue;
		}
		$out[] = array(
			'lat'   => (float) $lat,
			'lng'   => (float) $lng,
			'title' => get_the_title( $id ),
			'place' => awd_meta( $id, 'location' ) . ( 'Online' === awd_meta( $id, 'mode' ) ? ' · ' . __( 'sessions online', 'awenly-directory' ) : '' ),
			'url'   => get_permalink( $id ),
		);
	}
	return $out;
}

function awd_enqueue_map() {
	wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
	wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
	wp_enqueue_script( 'awenly-directory-map', AWD_URI . 'assets/map.js', array( 'leaflet' ), AWD_VERSION, true );
}

function awd_render_map( $points, $class = '' ) {
	if ( ! $points ) {
		return;
	}
	awd_enqueue_map();
	echo '<div class="awd-map ' . esc_attr( $class ) . '" data-points="' . esc_attr( wp_json_encode( $points ) ) . '" role="region" aria-label="' . esc_attr__( 'Map of listings', 'awenly-directory' ) . '"></div>';
}

add_filter( 'litespeed_optimize_js_excludes', function ( $list ) {
	$list[] = 'leaflet';
	$list[] = 'awenly-directory/assets/map.js';
	return $list;
} );

/* ===================== Practitioner events (The Events Calendar) ===================== */

function awd_events_active() {
	return function_exists( 'tribe_get_events' ) && post_type_exists( 'tribe_events' );
}

function awd_practitioner_events( $user_id, $limit = 4 ) {
	if ( ! awd_events_active() || ! $user_id ) {
		return array();
	}
	return tribe_get_events( array(
		'author'         => (int) $user_id,
		'posts_per_page' => $limit,
		'start_date'     => 'now',
		'eventDisplay'   => 'list',
	) );
}

/**
 * Event submission from the "My Practice" tab → pending tribe_events post.
 */
add_action( 'bp_actions', function () {
	if ( empty( $_POST['awd_action'] ) || 'submit_event' !== $_POST['awd_action'] || ! is_user_logged_in() || ! awd_events_active() ) {
		return;
	}
	$user_id = get_current_user_id();
	if ( (int) bp_displayed_user_id() !== $user_id ) {
		return;
	}
	if ( ! isset( $_POST['awd_enonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_enonce'] ) ), 'awd_event_' . $user_id ) ) {
		wp_die( esc_html__( 'Your session expired. Please go back and try again.', 'awenly-directory' ) );
	}
	$back    = trailingslashit( bp_loggedin_user_domain() ) . 'practice/#events';
	$pub     = awd_get_user_listings( $user_id, true );
	$listing = $pub ? $pub[0] : null;
	if ( ! $listing ) {
		wp_safe_redirect( add_query_arg( 'awd', 'event_needs_listing', $back ) );
		exit;
	}
	$title = sanitize_text_field( wp_unslash( $_POST['awd_ev_title'] ?? '' ) );
	$date  = sanitize_text_field( wp_unslash( $_POST['awd_ev_date'] ?? '' ) );
	$start = sanitize_text_field( wp_unslash( $_POST['awd_ev_start'] ?? '' ) );
	$end   = sanitize_text_field( wp_unslash( $_POST['awd_ev_end'] ?? '' ) );
	$where = sanitize_text_field( wp_unslash( $_POST['awd_ev_where'] ?? '' ) );
	$link  = esc_url_raw( wp_unslash( $_POST['awd_ev_link'] ?? '' ), array( 'http', 'https' ) );
	$desc  = wp_kses_post( wp_unslash( $_POST['awd_ev_desc'] ?? '' ) );
	$valid = $title && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && preg_match( '/^\d{2}:\d{2}$/', $start ) && strtotime( $date . ' ' . $start ) > time();
	if ( ! $valid ) {
		wp_safe_redirect( add_query_arg( 'awd', 'event_invalid', $back ) );
		exit;
	}
	if ( ! preg_match( '/^\d{2}:\d{2}$/', $end ) || $end <= $start ) {
		$end = gmdate( 'H:i', strtotime( $date . ' ' . $start ) + HOUR_IN_SECONDS );
	}
	$desc .= $where ? "\n\n<p><strong>" . esc_html__( 'Where:', 'awenly-directory' ) . '</strong> ' . esc_html( $where ) . '</p>' : '';
	$args = array(
		'post_title'     => mb_substr( $title, 0, 140 ),
		'post_content'   => $desc,
		'post_status'    => 'pending',
		'post_author'    => $user_id,
		'EventStartDate' => $date,
		'EventEndDate'   => $date,
		'EventStartHour' => substr( $start, 0, 2 ),
		'EventStartMinute' => substr( $start, 3, 2 ),
		'EventEndHour'   => substr( $end, 0, 2 ),
		'EventEndMinute' => substr( $end, 3, 2 ),
		'EventURL'       => $link,
	);
	$event_id = function_exists( 'tribe_create_event' ) ? tribe_create_event( $args ) : 0;
	if ( $event_id ) {
		update_post_meta( $event_id, '_awenly_event_location', $where );
		update_post_meta( $event_id, '_awd_listing', $listing->ID );
		wp_mail(
			get_option( 'admin_email' ),
			sprintf( '[%s] New event to approve: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $title ),
			sprintf( "%s submitted an event (%s %s).\n\nReview and publish it here:\n%s\n", wp_get_current_user()->display_name, $date, $start, admin_url( 'post.php?post=' . $event_id . '&action=edit' ) )
		);
	}
	wp_safe_redirect( add_query_arg( 'awd', $event_id ? 'event_submitted' : 'error', $back ) );
	exit;
} );

/**
 * "My events" block inside the My Practice tab.
 */
function awd_render_event_form( $listing ) {
	if ( ! awd_events_active() ) {
		return;
	}
	$user_id = get_current_user_id();
	$mine    = get_posts( array(
		'post_type'      => 'tribe_events',
		'author'         => $user_id,
		'post_status'    => array( 'publish', 'pending', 'draft' ),
		'posts_per_page' => 10,
		'meta_key'       => '_EventStartDate',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	) );
	?>
	<section class="awd-events-box" id="events">
		<h3><?php esc_html_e( 'My events', 'awenly-directory' ); ?></h3>
		<?php if ( ! $listing || 'publish' !== $listing->post_status ) : ?>
			<p class="awd-muted"><?php esc_html_e( 'Once your listing is approved you can add your workshops, circles and sessions here.', 'awenly-directory' ); ?></p>
		<?php else : ?>
			<?php if ( $mine ) : ?>
				<ul class="awd-my-events">
					<?php foreach ( $mine as $e ) :
						$st = 'publish' === $e->post_status ? array( 'live', __( 'Live', 'awenly-directory' ) ) : array( 'pending', __( 'Waiting for approval', 'awenly-directory' ) ); ?>
						<li><span class="awd-status--<?php echo esc_attr( $st[0] ); ?>"><span><?php echo esc_html( $st[1] ); ?></span></span>
							<strong><?php echo esc_html( get_the_title( $e ) ); ?></strong>
							<em><?php echo esc_html( function_exists( 'tribe_get_start_date' ) ? tribe_get_start_date( $e->ID, true, 'D j M Y' ) : '' ); ?></em>
							<?php if ( 'publish' === $e->post_status ) : ?><a href="<?php echo esc_url( get_permalink( $e ) ); ?>"><?php esc_html_e( 'View', 'awenly-directory' ); ?></a><?php endif; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<details class="awd-event-add"><summary class="awd-btn awd-btn--ghost"><?php esc_html_e( '+ Add an event', 'awenly-directory' ); ?></summary>
				<form class="awd-form" method="post">
					<?php wp_nonce_field( 'awd_event_' . $user_id, 'awd_enonce' ); ?>
					<input type="hidden" name="awd_action" value="submit_event">
					<div class="awd-grid">
						<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Event title *', 'awenly-directory' ); ?></span><input type="text" name="awd_ev_title" required maxlength="140"></label>
						<label class="awd-field"><span><?php esc_html_e( 'Date *', 'awenly-directory' ); ?></span><input type="date" name="awd_ev_date" required min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
						<label class="awd-field"><span><?php esc_html_e( 'Where (city, venue or "Online")', 'awenly-directory' ); ?></span><input type="text" name="awd_ev_where" maxlength="120"></label>
						<label class="awd-field"><span><?php esc_html_e( 'Start time *', 'awenly-directory' ); ?></span><input type="time" name="awd_ev_start" required></label>
						<label class="awd-field"><span><?php esc_html_e( 'End time', 'awenly-directory' ); ?></span><input type="time" name="awd_ev_end"></label>
						<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Booking / Zoom link', 'awenly-directory' ); ?></span><input type="url" name="awd_ev_link" placeholder="https://"></label>
						<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Description', 'awenly-directory' ); ?></span><textarea name="awd_ev_desc" rows="5"></textarea></label>
					</div>
					<button class="awd-btn" type="submit"><?php esc_html_e( 'Submit event for review', 'awenly-directory' ); ?></button>
				</form>
			</details>
		<?php endif; ?>
	</section>
	<?php
}
