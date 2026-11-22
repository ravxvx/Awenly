<?php
/**
 * Import events from Google's events search (via SerpApi) by keywords and country.
 *
 * Events → Import from Google. Each run searches "<keyword> in <city/country>", skips events that
 * already exist or are in the past, and creates The Events Calendar events (draft by default) with
 * a short original summary and a "Tickets / more info" link to the organiser's page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AWD_GI_OPT  = 'awd_gimport';
const AWD_GI_LOG  = 'awd_gimport_log';
const AWD_GI_HOOK = 'awd_gimport_cron';

function awd_gi_defaults() {
	return array(
		'key'      => '',
		'keywords' => "sound bath\nreiki\nbreathwork\nmeditation workshop\nyoga retreat\nhealing circle\nwellbeing workshop",
		'country'  => 'Ireland',
		'cities'   => '',
		'status'   => 'draft',
		'freq'     => 'off',
		'max_new'  => 20,
		'cat'      => 0,
		'online'   => 0,
		'images'   => 0,
	);
}

function awd_gi_get() {
	$o = get_option( AWD_GI_OPT, array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), awd_gi_defaults() );
}

function awd_gi_lines( $text ) {
	return array_values( array_unique( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) $text ) ) ) ) );
}

/** All "keyword in place" queries for one run. */
function awd_gi_queries( $o ) {
	$places = awd_gi_lines( $o['cities'] );
	$places = $places ? array_map( function ( $c ) use ( $o ) {
		return $o['country'] && false === stripos( $c, $o['country'] ) ? $c . ', ' . $o['country'] : $c;
	}, $places ) : array( $o['country'] );
	$q = array();
	foreach ( awd_gi_lines( $o['keywords'] ) as $kw ) {
		foreach ( $places as $p ) {
			$q[] = array( 'kw' => $kw, 'q' => $p ? $kw . ' in ' . $p : $kw, 'place' => $p );
		}
	}
	return $q;
}

function awd_gi_runs_per_month( $freq ) {
	return array( 'daily' => 30, 'twicedaily' => 60, 'weekly' => 4 )[ $freq ] ?? 0;
}

/* ---------- schedule ---------- */

function awd_gi_reschedule( $freq ) {
	wp_clear_scheduled_hook( AWD_GI_HOOK );
	if ( in_array( $freq, array( 'daily', 'twicedaily', 'weekly' ), true ) ) {
		wp_schedule_event( time() + ( 'weekly' === $freq ? DAY_IN_SECONDS : HOUR_IN_SECONDS ), $freq, AWD_GI_HOOK );
	}
}
add_action( AWD_GI_HOOK, function () {
	awd_gi_run( false );
} );

/* ---------- SerpApi ---------- */

function awd_gi_search( $key, $q, $place, &$debug = null ) {
	return awd_gi_search_once( $key, $q, $place, $debug );
}

function awd_gi_search_once( $key, $q, $place, &$debug = null ) {
	$args = array( 'engine' => 'google_events', 'q' => $q, 'api_key' => $key, 'output' => 'json' );
	if ( $place ) {
		$args['location'] = $place;
	}
	$r = wp_remote_get( add_query_arg( array_map( 'rawurlencode', $args ), 'https://serpapi.com/search.json' ), array( 'timeout' => 45 ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$body = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'bad', 'SerpApi returned HTTP ' . wp_remote_retrieve_response_code( $r ) );
	}
	$debug[] = ( $place ? 'location=' . $place : 'no location' ) . ' → ' . ( ! empty( $body['error'] ) ? $body['error'] : count( (array) ( $body['events_results'] ?? array() ) ) . ' results' )
		. ( ! empty( $body['search_metadata']['google_events_url'] ) ? ' · ' . $body['search_metadata']['google_events_url'] : '' );
	if ( ! empty( $body['error'] ) ) {
		// "Google hasn't returned any results" is not a failure, just an empty search.
		return false !== stripos( $body['error'], "hasn't returned any results" ) ? array() : new WP_Error( 'api', $body['error'] );
	}
	return isset( $body['events_results'] ) && is_array( $body['events_results'] ) ? $body['events_results'] : array();
}

/* ---------- date parsing ---------- */

/** Parse Google's "when" text into start/end timestamps (site time). Returns null if no date. */
function awd_gi_parse_when( $when, $start_date = '' ) {
	$when = str_replace( array( "\u{2013}", "\u{2014}", "\u{202f}", "\u{a0}" ), array( '-', '-', ' ', ' ' ), (string) $when );
	$mon  = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec)[a-z]*\.?';
	if ( ! preg_match_all( '/' . $mon . '\s+(\d{1,2})(?:,?\s+(\d{4}))?/i', $when, $dm, PREG_SET_ORDER ) ) {
		if ( ! preg_match_all( '/' . $mon . '\s+(\d{1,2})/i', (string) $start_date, $dm, PREG_SET_ORDER ) ) {
			return null;
		}
	}
	$tz    = wp_timezone();
	$now   = new DateTimeImmutable( 'now', $tz );
	$mk    = function ( $m ) use ( $tz, $now ) {
		$y = ! empty( $m[3] ) ? (int) $m[3] : (int) $now->format( 'Y' );
		$d = DateTimeImmutable::createFromFormat( '!M j Y', substr( ucfirst( strtolower( $m[1] ) ), 0, 3 ) . ' ' . (int) $m[2] . ' ' . $y, $tz );
		if ( $d && empty( $m[3] ) && $d < $now->modify( '-45 days' ) ) {
			$d = $d->modify( '+1 year' ); // "Jan 10" seen in December means next year.
		}
		return $d;
	};
	$sd = $mk( $dm[0] );
	if ( ! $sd ) {
		return null;
	}
	$ed = isset( $dm[1] ) ? $mk( $dm[1] ) : $sd;
	if ( ! $ed || $ed < $sd ) {
		$ed = $sd;
	}
	// Times: "7 - 9 PM", "10:00 AM - 1:30 PM", "8:30 PM".
	preg_match_all( '/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\b(?=\s*(?:-|am|pm|$|[A-Z]{2,4}\b|,))/i', preg_replace( '/' . $mon . '\s+\d{1,2}(,?\s+\d{4})?/i', ' ', $when ), $tm, PREG_SET_ORDER );
	$times = array();
	foreach ( $tm as $t ) {
		if ( (int) $t[1] > 23 || ( empty( $t[3] ) && empty( $t[2] ) ) && ! preg_match( '/\d\s*-\s*\d/', $when ) ) {
			continue;
		}
		$times[] = array( (int) $t[1], isset( $t[2] ) && '' !== $t[2] ? (int) $t[2] : 0, strtolower( $t[3] ?? '' ) );
	}
	$times = array_slice( $times, 0, 2 );
	if ( isset( $times[1] ) && ! $times[0][2] ) {
		$times[0][2] = $times[1][2];
	}
	$h24 = function ( $t ) {
		$h = $t[0];
		if ( 'pm' === $t[2] && $h < 12 ) {
			$h += 12;
		} elseif ( 'am' === $t[2] && 12 === $h ) {
			$h = 0;
		}
		return array( $h, $t[1] );
	};
	if ( ! $times || ! array_filter( $times, function ( $t ) {
		return '' !== $t[2] || $t[1];
	} ) ) {
		return array( 'start' => $sd, 'end' => $ed, 'allday' => true );
	}
	list( $h, $m ) = $h24( $times[0] );
	$start         = $sd->setTime( $h, $m );
	if ( isset( $times[1] ) ) {
		list( $h, $m ) = $h24( $times[1] );
		$end           = $ed->setTime( $h, $m );
		if ( $end <= $start ) {
			$end = $start->modify( '+2 hours' );
		}
	} else {
		$end = $ed > $sd ? $ed->setTime( 17, 0 ) : $start->modify( '+2 hours' );
	}
	return array( 'start' => $start, 'end' => $end, 'allday' => false );
}

/* ---------- import ---------- */

/** Words that make an event relevant to a wellbeing directory (stems). */
function awd_gi_topic_words( $keywords ) {
	$base = array( 'yoga', 'meditat', 'breath', 'sound bath', 'sound heal', 'sound journey', 'gong', 'reiki', 'heal', 'holistic', 'mindful', 'wellness', 'wellbeing', 'well-being', 'retreat', 'pilates', 'qigong', 'qi gong', 'tai chi', 'cacao', 'kirtan', 'chakra', 'spiritual', 'ecstatic dance', 'sauna', 'reflexolog', 'massage', 'ayurved', 'nidra', 'somatic', 'kundalini', 'crystal', 'energy', 'self-care', 'self care', 'stress', 'relax', 'womb', 'moon circle', 'women\'s circle', 'mens circle', 'men\'s circle', 'shaman', 'hypno', 'nutrition', 'forest bathing', 'cold water', 'wim hof' );
	foreach ( awd_gi_lines( $keywords ) as $k ) {
		$base[] = strtolower( rtrim( $k, 's' ) );
	}
	return array_unique( $base );
}

function awd_gi_relevant( $text, $words ) {
	$text = strtolower( $text );
	foreach ( $words as $w ) {
		if ( strlen( $w ) > 2 && false !== strpos( $text, $w ) ) {
			return true;
		}
	}
	return false;
}

function awd_gi_key( $title, $date ) {
	return md5( strtolower( preg_replace( '/[^a-z0-9]+/i', '', $title ) ) . '|' . $date );
}

function awd_gi_exists( $key, $title, $date ) {
	$q = get_posts( array( 'post_type' => 'tribe_events', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_awd_import_key', 'meta_value' => $key ) );
	if ( $q ) {
		return true;
	}
	// Same title on the same day from any source (member-submitted, manual, Eventbrite…).
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_EventStartDate'
		 WHERE p.post_type = 'tribe_events' AND p.post_status NOT IN ('trash','auto-draft') AND p.post_title = %s AND m.meta_value LIKE %s LIMIT 1",
		$title,
		$wpdb->esc_like( $date ) . '%'
	) );
}

function awd_gi_category( $kw, $title, $default ) {
	if ( ! taxonomy_exists( 'tribe_events_cat' ) ) {
		return 0;
	}
	$hay   = strtolower( $kw . ' ' . $title );
	$best  = 0;
	$blen  = 0;
	$terms = get_terms( array( 'taxonomy' => 'tribe_events_cat', 'hide_empty' => false ) );
	foreach ( is_array( $terms ) ? $terms : array() as $t ) {
		foreach ( array_filter( array_map( 'trim', preg_split( '/[&,\/]| and /i', strtolower( $t->name ) ) ) ) as $w ) {
			$stem = rtrim( $w, 's' );
			if ( strlen( $stem ) > 3 && false !== strpos( $hay, $stem ) && strlen( $stem ) > $blen ) {
				$best = $t->term_id;
				$blen = strlen( $stem );
			}
		}
	}
	return $best ? $best : (int) $default;
}

function awd_gi_summary( $e, $when, $where, $kw ) {
	$type  = ! empty( $e['type'] ) ? $e['type'] : '';
	$date  = $when['start']->format( 'l j F Y' ) . ( $when['allday'] ? '' : ', ' . $when['start']->format( 'g:i a' ) );
	$venue = ! empty( $e['venue']['name'] ) ? $e['venue']['name'] : $where;
	$p1    = sprintf(
		'<strong>%s</strong> is %s%s on %s.',
		esc_html( $e['title'] ),
		$type ? 'a ' . esc_html( strtolower( $type ) ) : 'a ' . esc_html( $kw ) . ' event',
		$venue ? ' at ' . esc_html( $venue ) : '',
		esc_html( $date )
	);
	$p2    = 'The event is run by an independent organiser. Use the <em>Book / more info</em> button to see the full programme, price and booking on the organiser\'s own page — and check the details there before you travel.';
	$src   = ! empty( $e['ticket_info'][0]['source'] ) ? $e['ticket_info'][0]['source'] : '';
	$p3    = 'Are you the organiser? <a href="/add-events/">Add your events to Awenly Circle</a> or <a href="/contact/">contact us</a> to update or remove this listing.';
	return '<p>' . $p1 . '</p><p>' . $p2 . '</p>' . ( $src ? '<p><small>Listing found via Google events · tickets on ' . esc_html( $src ) . '</small></p>' : '' ) . '<p>' . $p3 . '</p>';
}

function awd_gi_link( $e ) {
	foreach ( (array) ( $e['ticket_info'] ?? array() ) as $t ) {
		if ( ! empty( $t['link'] ) && 'tickets' === ( $t['link_type'] ?? '' ) ) {
			return $t['link'];
		}
	}
	foreach ( (array) ( $e['ticket_info'] ?? array() ) as $t ) {
		if ( ! empty( $t['link'] ) ) {
			return $t['link'];
		}
	}
	if ( ! empty( $e['link'] ) ) {
		return $e['link'];
	}
	if ( ! empty( $e['event_location_map']['link'] ) && empty( $e['venue']['link'] ) ) {
		return $e['event_location_map']['link'];
	}
	// Last resort: a Google search for the event, which shows Google's own event card with the organiser's links.
	return 'https://www.google.com/search?q=' . rawurlencode( trim( ( $e['title'] ?? '' ) . ' ' . ( $e['venue']['name'] ?? ( $e['address'][0] ?? '' ) ) ) ) . '&ibp=htl;events';
}

/**
 * Run the import. $dry = true returns what would be imported without saving (uses the first query only).
 */
function awd_gi_run( $dry = false ) {
	$o   = awd_gi_get();
	$log = array( 'time' => time(), 'dry' => $dry, 'searches' => 0, 'found' => 0, 'new' => 0, 'skipped' => 0, 'errors' => array(), 'items' => array() );
	if ( ! $o['key'] ) {
		$log['errors'][] = 'No SerpApi key saved.';
		return $log;
	}
	if ( ! function_exists( 'tribe_create_event' ) ) {
		$log['errors'][] = 'The Events Calendar is not active.';
		return $log;
	}
	if ( ! $dry && get_transient( 'awd_gi_lock' ) ) {
		$log['errors'][] = 'An import is already running.';
		return $log;
	}
	if ( ! $dry ) {
		set_transient( 'awd_gi_lock', 1, 15 * MINUTE_IN_SECONDS );
	}
	$queries = awd_gi_queries( $o );
	if ( $dry ) {
		$queries = array_slice( $queries, 0, 1 );
	}
	$seen  = array();
	$words = awd_gi_topic_words( $o['keywords'] );
	$today = new DateTimeImmutable( 'today', wp_timezone() );
	foreach ( $queries as $qq ) {
		if ( ! $dry && $log['new'] >= (int) $o['max_new'] ) {
			break;
		}
		$dbg = array();
		$res = awd_gi_search( $o['key'], $qq['q'], $qq['place'], $dbg );
		$log['searches'] += count( $dbg );
		if ( $dry ) {
			$log['debug'] = array_merge( $log['debug'] ?? array(), array_map( function ( $d ) use ( $qq ) {
				return '"' . $qq['q'] . '" ' . $d;
			}, $dbg ) );
		}
		if ( is_wp_error( $res ) ) {
			$log['errors'][] = $qq['q'] . ': ' . $res->get_error_message();
			if ( preg_match( '/run out|invalid api key|limit/i', $res->get_error_message() ) ) {
				break;
			}
			continue;
		}
		foreach ( $res as $e ) {
			if ( empty( $e['title'] ) ) {
				continue;
			}
			$log['found']++;
			$title = trim( wp_strip_all_tags( $e['title'] ) );
			$when  = awd_gi_parse_when( $e['date']['when'] ?? '', $e['date']['start_date'] ?? '' );
			$addr  = implode( ', ', array_filter( array_map( 'trim', (array) ( $e['address'] ?? array() ) ) ) );
			$is_ol = (bool) preg_match( '/\b(online|virtual|zoom)\b/i', $title . ' ' . $addr );
			$why   = '';
			if ( ! $when ) {
				$why = 'no date';
			} elseif ( $when['start'] < $today ) {
				$why = 'in the past';
			} elseif ( $is_ol && ! $o['online'] ) {
				$why = 'online event';
			} elseif ( ! awd_gi_relevant( $title . ' ' . ( $e['type'] ?? '' ) . ' ' . ( $e['description'] ?? '' ), $words ) ) {
				$why = 'not a wellbeing event';
			}
			$date = $when ? $when['start']->format( 'Y-m-d' ) : '';
			$key  = awd_gi_key( $title, $date );
			if ( ! $why && ( isset( $seen[ $key ] ) || awd_gi_exists( $key, $title, $date ) ) ) {
				$why = 'already on the site';
			}
			$seen[ $key ] = 1;
			$item         = array( 'title' => $title, 'when' => $e['date']['when'] ?? '', 'where' => $addr, 'link' => awd_gi_link( $e ), 'query' => $qq['q'] );
			if ( $why || $dry || $log['new'] >= (int) $o['max_new'] ) {
				$item['status'] = $why ? $why : ( $dry ? 'would import' : 'limit reached' );
				$log['skipped'] += $why ? 1 : 0;
				$log['items'][]  = $item;
				if ( $dry && ! $why ) {
					$log['new']++;
				}
				continue;
			}
			$args = array(
				'post_title'       => mb_substr( $title, 0, 140 ),
				'post_content'     => awd_gi_summary( $e, $when, $addr, $qq['kw'] ),
				'post_status'      => 'publish' === $o['status'] ? 'publish' : 'draft',
				'post_author'      => get_current_user_id() ? get_current_user_id() : (int) ( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 1 ),
				'EventStartDate'   => $when['start']->format( 'Y-m-d' ),
				'EventEndDate'     => $when['end']->format( 'Y-m-d' ),
				'EventURL'         => esc_url_raw( $item['link'] ),
				'EventCost'        => $e['price'] ?? '',
			);
			if ( $when['allday'] ) {
				$args['EventAllDay'] = 'yes';
			} else {
				$args += array(
					'EventStartHour'   => $when['start']->format( 'H' ),
					'EventStartMinute' => $when['start']->format( 'i' ),
					'EventEndHour'     => $when['end']->format( 'H' ),
					'EventEndMinute'   => $when['end']->format( 'i' ),
				);
			}
			$id = tribe_create_event( $args );
			if ( ! $id || is_wp_error( $id ) ) {
				$log['errors'][] = 'Could not save "' . $title . '"';
				continue;
			}
			update_post_meta( $id, '_awenly_event_location', $is_ol ? 'Online' . ( $addr ? ' · ' . $addr : '' ) : $addr );
			update_post_meta( $id, '_EventURL', esc_url_raw( $item['link'] ) );
			update_post_meta( $id, '_awd_import_raw', wp_slash( wp_json_encode( array_intersect_key( $e, array_flip( array( 'title', 'date', 'address', 'link', 'ticket_info', 'venue', 'type', 'description', 'price' ) ) ) ) ) );
			update_post_meta( $id, '_awd_imported', 'google' );
			update_post_meta( $id, '_awd_import_key', $key );
			update_post_meta( $id, '_awd_import_query', $qq['q'] );
			update_post_meta( $id, 'rank_math_description', mb_substr( wp_strip_all_tags( sprintf( '%s — %s%s. Find details and tickets on Awenly Circle, the wellbeing events directory.', $title, $when['start']->format( 'j M Y' ), $addr ? ', ' . $addr : '' ) ), 0, 158 ) );
			$cat = awd_gi_category( $qq['kw'], $title, $o['cat'] );
			if ( $cat ) {
				wp_set_object_terms( $id, array( $cat ), 'tribe_events_cat' );
			}
			if ( $o['images'] && ! empty( $e['thumbnail'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$att = media_sideload_image( $e['thumbnail'], $id, $title, 'id' );
				if ( ! is_wp_error( $att ) ) {
					set_post_thumbnail( $id, $att );
				}
			}
			$item['status'] = 'imported';
			$item['id']     = $id;
			$log['items'][] = $item;
			$log['new']++;
		}
	}
	if ( ! $dry ) {
		delete_transient( 'awd_gi_lock' );
		$hist = get_option( AWD_GI_LOG, array() );
		$keep = $log;
		$keep['items'] = array_slice( array_values( array_filter( $log['items'], function ( $i ) {
			return 'imported' === $i['status'];
		} ) ), 0, 50 );
		array_unshift( $hist, $keep );
		update_option( AWD_GI_LOG, array_slice( $hist, 0, 15 ), false );
		awd_gi_cleanup();
	}
	return $log;
}

/** Bin imported drafts whose date has passed (nobody approved them in time). */
function awd_gi_cleanup() {
	$old = get_posts( array(
		'post_type'      => 'tribe_events',
		'post_status'    => 'draft',
		'fields'         => 'ids',
		'posts_per_page' => 100,
		'meta_query'     => array(
			array( 'key' => '_awd_imported', 'value' => 'google' ),
			array( 'key' => '_EventEndDate', 'value' => current_time( 'mysql' ), 'compare' => '<', 'type' => 'DATETIME' ),
		),
	) );
	foreach ( $old as $id ) {
		wp_trash_post( $id );
	}
}

/* ---------- admin page ---------- */

add_action( 'admin_menu', function () {
	if ( ! post_type_exists( 'tribe_events' ) ) {
		return;
	}
	add_submenu_page( 'edit.php?post_type=tribe_events', 'Import from Google', 'Import from Google', 'manage_options', 'awd-gimport', 'awd_gi_page' );
}, 30 );

add_action( 'admin_post_awd_gimport', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}
	check_admin_referer( 'awd_gimport' );
	$o   = awd_gi_get();
	$in  = wp_unslash( $_POST );
	$key = trim( sanitize_text_field( $in['key'] ?? '' ) );
	if ( $key && false === strpos( $key, '•' ) ) {
		$o['key'] = $key;
	}
	if ( ! empty( $in['forget_key'] ) ) {
		$o['key'] = '';
	}
	$o['keywords'] = implode( "\n", array_slice( awd_gi_lines( sanitize_textarea_field( $in['keywords'] ?? '' ) ), 0, 40 ) );
	$o['country']  = sanitize_text_field( $in['country'] ?? '' );
	$o['cities']   = implode( "\n", array_slice( awd_gi_lines( sanitize_textarea_field( $in['cities'] ?? '' ) ), 0, 20 ) );
	$o['status']   = 'publish' === ( $in['status'] ?? '' ) ? 'publish' : 'draft';
	$o['freq']     = in_array( $in['freq'] ?? '', array( 'off', 'daily', 'twicedaily', 'weekly' ), true ) ? $in['freq'] : 'off';
	$o['max_new']  = max( 1, min( 100, (int) ( $in['max_new'] ?? 20 ) ) );
	$o['cat']      = (int) ( $in['cat'] ?? 0 );
	$o['online']   = empty( $in['online'] ) ? 0 : 1;
	$o['images']   = empty( $in['images'] ) ? 0 : 1;
	$prev_freq = awd_gi_get()['freq'];
	update_option( AWD_GI_OPT, $o, false );
	if ( $prev_freq !== $o['freq'] || ( 'off' !== $o['freq'] && ! wp_next_scheduled( AWD_GI_HOOK ) ) ) {
		awd_gi_reschedule( $o['freq'] );
	}
	$msg = 'saved';
	if ( ! empty( $in['do'] ) && in_array( $in['do'], array( 'test', 'run' ), true ) ) {
		set_transient( 'awd_gi_result_' . get_current_user_id(), awd_gi_run( 'test' === $in['do'] ), 10 * MINUTE_IN_SECONDS );
		$msg = $in['do'];
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=tribe_events&page=awd-gimport&msg=' . $msg ) );
	exit;
} );

function awd_gi_page() {
	$o       = awd_gi_get();
	$queries = awd_gi_queries( $o );
	$per_run = count( $queries );
	$month   = $per_run * awd_gi_runs_per_month( $o['freq'] );
	$result  = get_transient( 'awd_gi_result_' . get_current_user_id() );
	delete_transient( 'awd_gi_result_' . get_current_user_id() );
	$waiting = (int) ( new WP_Query( array( 'post_type' => 'tribe_events', 'post_status' => 'draft', 'meta_key' => '_awd_imported', 'meta_value' => 'google', 'fields' => 'ids', 'posts_per_page' => 1 ) ) )->found_posts;
	$cats    = taxonomy_exists( 'tribe_events_cat' ) ? get_terms( array( 'taxonomy' => 'tribe_events_cat', 'hide_empty' => false ) ) : array();
	$next    = wp_next_scheduled( AWD_GI_HOOK );
	$hist    = get_option( AWD_GI_LOG, array() );
	$msg     = sanitize_key( $_GET['msg'] ?? '' );
	?>
	<div class="wrap">
		<h1>Import events from Google</h1>
		<p style="max-width:780px">Searches Google's events listings for each <strong>keyword</strong> in your <strong>country</strong> (or each city), and adds new upcoming events to the calendar with a short summary and a <em>Book / more info</em> link to the organiser. Events already on the site, past events and duplicates are skipped. Uses <a href="https://serpapi.com/google-events-api" target="_blank" rel="noopener">SerpApi</a> — the free plan includes 100 searches a month.</p>
		<?php if ( 'saved' === $msg ) : ?><div class="notice notice-success is-dismissible"><p>Settings saved.</p></div><?php endif; ?>
		<?php if ( $waiting ) : ?>
			<div class="notice notice-info"><p><strong><?php echo (int) $waiting; ?></strong> imported events are waiting as drafts. <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tribe_events&post_status=draft' ) ); ?>">Review and publish them</a>.</p></div>
		<?php endif; ?>
		<?php if ( $result ) : ?>
			<div class="notice <?php echo $result['errors'] ? 'notice-warning' : 'notice-success'; ?>">
				<p><strong><?php echo $result['dry'] ? 'Test search (nothing saved)' : 'Import finished'; ?>:</strong>
				<?php printf( '%d searches, %d events found, %d %s, %d skipped.', (int) $result['searches'], (int) $result['found'], (int) $result['new'], $result['dry'] ? 'would be imported' : 'imported', (int) $result['skipped'] ); ?></p>
				<?php foreach ( (array) ( $result['debug'] ?? array() ) as $dl ) : ?><p style="font-family:monospace;font-size:12px;color:#50575e;word-break:break-all"><?php echo esc_html( $dl ); ?></p><?php endforeach; ?>
				<?php foreach ( $result['errors'] as $er ) : ?><p style="color:#b32d2e"><?php echo esc_html( $er ); ?></p><?php endforeach; ?>
				<?php if ( $result['items'] ) : ?>
					<table class="widefat striped" style="margin:8px 0 12px"><thead><tr><th>Event</th><th>When</th><th>Where</th><th>Result</th></tr></thead><tbody>
					<?php foreach ( array_slice( $result['items'], 0, 60 ) as $it ) : ?>
						<tr><td><?php echo ! empty( $it['id'] ) ? '<a href="' . esc_url( get_edit_post_link( $it['id'] ) ) . '">' . esc_html( $it['title'] ) . '</a>' : ( $it['link'] ? '<a href="' . esc_url( $it['link'] ) . '" target="_blank" rel="noopener">' . esc_html( $it['title'] ) . '</a>' : esc_html( $it['title'] ) ); ?></td>
						<td><?php echo esc_html( $it['when'] ); ?></td><td><?php echo esc_html( $it['where'] ); ?></td><td><?php echo esc_html( $it['status'] ); ?></td></tr>
					<?php endforeach; ?>
					</tbody></table>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="awd_gimport">
			<?php wp_nonce_field( 'awd_gimport' ); ?>
			<table class="form-table" role="presentation">
				<tr><th><label for="gi-key">SerpApi key</label></th><td>
					<input id="gi-key" name="key" type="password" class="regular-text" autocomplete="off" value="<?php echo $o['key'] ? esc_attr( str_repeat( '•', 12 ) . substr( $o['key'], -4 ) ) : ''; ?>" placeholder="Paste your private API key">
					<?php if ( $o['key'] ) : ?><label style="margin-left:10px"><input type="checkbox" name="forget_key" value="1"> remove key</label><?php endif; ?>
					<p class="description">Free account at <a href="https://serpapi.com/users/sign_up" target="_blank" rel="noopener">serpapi.com</a> → Dashboard → <em>Your Private API Key</em>.</p>
				</td></tr>
				<tr><th><label for="gi-kw">Keywords</label></th><td>
					<textarea id="gi-kw" name="keywords" rows="7" class="large-text" style="max-width:420px"><?php echo esc_textarea( $o['keywords'] ); ?></textarea>
					<p class="description">One per line, e.g. <code>sound bath</code>, <code>reiki</code>, <code>breathwork</code>. The matching event category is chosen automatically.</p>
				</td></tr>
				<tr><th><label for="gi-country">Country</label></th><td>
					<input id="gi-country" name="country" list="gi-countries" class="regular-text" value="<?php echo esc_attr( $o['country'] ); ?>">
					<datalist id="gi-countries"><?php foreach ( array( 'Ireland', 'United Kingdom', 'Poland', 'United States', 'Canada', 'Australia', 'New Zealand', 'Germany', 'Netherlands', 'Spain', 'Portugal', 'France', 'Italy', 'Sweden', 'Denmark', 'Norway' ) as $c ) : ?><option value="<?php echo esc_attr( $c ); ?>"><?php endforeach; ?></datalist>
				</td></tr>
				<tr><th><label for="gi-cities">Cities <span style="font-weight:400">(optional)</span></label></th><td>
					<textarea id="gi-cities" name="cities" rows="4" class="large-text" style="max-width:420px" placeholder="Dublin&#10;Cork&#10;Galway"><?php echo esc_textarea( $o['cities'] ); ?></textarea>
					<p class="description">Leave empty to search the whole country. With cities, each keyword is searched in each city — more events, but more searches.</p>
				</td></tr>
				<tr><th>New events</th><td>
					<label><input type="radio" name="status" value="draft" <?php checked( 'draft', $o['status'] ); ?>> Save as drafts for me to approve <em>(recommended)</em></label><br>
					<label><input type="radio" name="status" value="publish" <?php checked( 'publish', $o['status'] ); ?>> Publish straight away</label><br>
					<label style="display:inline-block;margin-top:6px">At most <input name="max_new" type="number" min="1" max="100" value="<?php echo (int) $o['max_new']; ?>" style="width:70px"> new events per run</label>
				</td></tr>
				<tr><th><label for="gi-cat">Fallback category</label></th><td>
					<select id="gi-cat" name="cat"><option value="0">— none —</option><?php foreach ( is_array( $cats ) ? $cats : array() as $c ) : ?><option value="<?php echo (int) $c->term_id; ?>" <?php selected( (int) $o['cat'], (int) $c->term_id ); ?>><?php echo esc_html( $c->name ); ?></option><?php endforeach; ?></select>
				</td></tr>
				<tr><th>Options</th><td>
					<label><input type="checkbox" name="online" value="1" <?php checked( 1, (int) $o['online'] ); ?>> Include online events</label><br>
					<label><input type="checkbox" name="images" value="1" <?php checked( 1, (int) $o['images'] ); ?>> Download the event thumbnail as the featured image</label>
					<p class="description">Off by default: thumbnails belong to the organisers and are small. Without one, the event shows the category artwork.</p>
				</td></tr>
				<tr><th><label for="gi-freq">Run automatically</label></th><td>
					<select id="gi-freq" name="freq">
						<?php foreach ( array( 'off' => 'Off — only when I click Import now', 'weekly' => 'Weekly', 'daily' => 'Daily', 'twicedaily' => 'Twice a day' ) as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $o['freq'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?>
					</select>
					<?php if ( $next ) : ?><span class="description" style="margin-left:8px">Next run: <?php echo esc_html( wp_date( 'D j M, H:i', $next ) ); ?></span><?php endif; ?>
					<p class="description" style="<?php echo $month > 100 ? 'color:#b32d2e;font-weight:600' : ''; ?>">
						Each run uses <strong><?php echo (int) $per_run; ?></strong> searches<?php echo $month ? ' — about <strong>' . (int) $month . '</strong> a month' : ''; ?>.
						<?php echo $month > 100 ? 'That is more than the free 100 — use fewer keywords/cities or run weekly.' : ''; ?>
					</p>
				</td></tr>
			</table>
			<p>
				<button class="button button-primary" name="do" value="">Save settings</button>
				<button class="button" name="do" value="test" <?php disabled( ! $o['key'] ); ?> title="Runs the first search only and shows what would be imported. Uses 1 search.">Test (1 search, saves nothing)</button>
				<button class="button" name="do" value="run" <?php disabled( ! $o['key'] ); ?> onclick="return confirm('Run all <?php echo (int) $per_run; ?> searches now?')">Import now</button>
			</p>
		</form>

		<?php if ( $hist ) : ?>
			<h2>Recent imports</h2>
			<table class="widefat striped" style="max-width:780px"><thead><tr><th>When</th><th>Searches</th><th>Found</th><th>Imported</th><th>Problems</th></tr></thead><tbody>
			<?php foreach ( $hist as $h ) : ?>
				<tr><td><?php echo esc_html( wp_date( 'D j M, H:i', $h['time'] ) ); ?></td><td><?php echo (int) $h['searches']; ?></td><td><?php echo (int) $h['found']; ?></td><td><?php echo (int) $h['new']; ?></td><td><?php echo esc_html( implode( '; ', array_slice( $h['errors'], 0, 2 ) ) ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
	</div>
	<?php
}

/* Show the source on the edit screen of imported events. */
add_action( 'post_submitbox_misc_actions', function ( $post ) {
	if ( 'tribe_events' === $post->post_type && 'google' === get_post_meta( $post->ID, '_awd_imported', true ) ) {
		echo '<div class="misc-pub-section">Imported from Google · <em>' . esc_html( get_post_meta( $post->ID, '_awd_import_query', true ) ) . '</em></div>';
	}
} );

/* One-time: tidy events imported before the relevance filter and link fix (1.7.3). */
add_action( 'init', function () {
	if ( (int) get_option( 'awd_gi_fix_v' ) >= 1 || get_transient( 'awd_gi_fix_lock' ) || ! post_type_exists( 'tribe_events' ) ) {
		return;
	}
	set_transient( 'awd_gi_fix_lock', 1, 5 * MINUTE_IN_SECONDS );
	update_option( 'awd_gi_fix_v', 1, false );
	$words = awd_gi_topic_words( awd_gi_get()['keywords'] );
	$ids   = get_posts( array( 'post_type' => 'tribe_events', 'post_status' => array( 'publish', 'draft' ), 'fields' => 'ids', 'posts_per_page' => 200, 'meta_key' => '_awd_imported', 'meta_value' => 'google' ) );
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( 'publish' === $p->post_status && ! awd_gi_relevant( $p->post_title, $words ) ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		}
		if ( ! get_post_meta( $id, '_EventURL', true ) ) {
			$venue = '';
			if ( preg_match( '/ at (.+?) on [A-Z][a-z]+day /', wp_strip_all_tags( $p->post_content ), $m ) ) {
				$venue = $m[1];
			}
			update_post_meta( $id, '_EventURL', esc_url_raw( 'https://www.google.com/search?q=' . rawurlencode( trim( $p->post_title . ' ' . $venue ) ) . '&ibp=htl;events' ) );
		}
	}
	delete_transient( 'awd_gi_fix_lock' );
}, 30 );
