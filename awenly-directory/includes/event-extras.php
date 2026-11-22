<?php
/**
 * Event extras: category cover images, members-only joining links (Zoom/Meet/Teams),
 * share buttons, local time display and richer Event structured data for Google.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 1. Cover images — every event without a photo gets an Awenly category cover.
 * ====================================================================== */

function awd_ev_topics() {
	return array(
		'sound'      => '/\b(sound|gong|singing bowl|crystal bowl|kirtan|chant)/i',
		'breathwork' => '/\bbreath/i',
		'reiki'      => '/\b(reiki|energy heal|chakra|crystal heal)/i',
		'retreat'    => '/\b(retreat|weekend away|getaway)/i',
		'circle'     => '/\b(circle|sharing|women\'?s gathering|men\'?s gathering|community)/i',
		'yoga'       => '/\b(yoga|yin|vinyasa|hatha|nidra|asana)/i',
		'movement'   => '/\b(pilates|qigong|qi gong|tai chi|ecstatic dance|movement|somatic)/i',
		'meditation' => '/\b(meditat|mindful|contemplat|silence)/i',
		'online'     => '/\b(online|zoom|virtual|webinar|livestream)/i',
	);
}

function awd_ev_topic( $id ) {
	$text = get_the_title( $id ) . ' ' . get_post_meta( $id, '_awenly_event_location', true );
	$cats = get_the_terms( $id, 'tribe_events_cat' );
	if ( $cats && ! is_wp_error( $cats ) ) {
		$text .= ' ' . implode( ' ', wp_list_pluck( $cats, 'name' ) );
	}
	foreach ( awd_ev_topics() as $slug => $re ) {
		if ( preg_match( $re, $text ) ) {
			return $slug;
		}
	}
	return 'wellbeing';
}

/** Attachment ID for a topic cover, importing the bundled image into the media library the first time. */
function awd_ev_cover_id( $topic ) {
	$map = (array) get_option( 'awd_ev_covers', array() );
	if ( ! empty( $map[ $topic ] ) && 'attachment' === get_post_type( $map[ $topic ] ) ) {
		return (int) $map[ $topic ];
	}
	$file = AWD_DIR . 'assets/event-covers/' . $topic . '.jpg';
	if ( ! file_exists( $file ) ) {
		return 0;
	}
	$up = wp_upload_bits( 'awenly-event-' . $topic . '.jpg', null, file_get_contents( $file ) ); // phpcs:ignore
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$labels = array(
		'sound'      => 'Sound bath and sound healing event',
		'breathwork' => 'Breathwork session',
		'reiki'      => 'Reiki and energy healing event',
		'retreat'    => 'Wellness retreat',
		'circle'     => 'Healing and sharing circle',
		'yoga'       => 'Yoga class and workshop',
		'movement'   => 'Pilates, qigong and tai chi class',
		'meditation' => 'Meditation and mindfulness event',
		'online'     => 'Online wellbeing session',
		'wellbeing'  => 'Holistic wellbeing event',
	);
	$att = wp_insert_attachment( array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => ( $labels[ $topic ] ?? 'Wellbeing event' ) . ' – Awenly Circle',
		'post_status'    => 'inherit',
	), $up['file'] );
	if ( ! $att || is_wp_error( $att ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $up['file'] ) );
	update_post_meta( $att, '_wp_attachment_image_alt', $labels[ $topic ] ?? 'Wellbeing event' );
	$map[ $topic ] = $att;
	update_option( 'awd_ev_covers', $map, false );
	return (int) $att;
}

function awd_ev_set_cover( $id ) {
	if ( has_post_thumbnail( $id ) && ! get_post_meta( $id, '_awd_auto_cover', true ) ) {
		return; // The organiser's own photo always wins.
	}
	$att = awd_ev_cover_id( awd_ev_topic( $id ) );
	if ( $att && (int) get_post_thumbnail_id( $id ) !== $att ) {
		set_post_thumbnail( $id, $att );
		update_post_meta( $id, '_awd_auto_cover', 1 );
	}
}

add_action( 'save_post_tribe_events', function ( $id, $post ) {
	if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
		return;
	}
	awd_ev_set_cover( $id );
}, 30, 2 );

/* A photo uploaded later (member form or editor) replaces the automatic cover. */
add_action( 'updated_post_meta', function ( $mid, $id, $key ) {
	if ( '_thumbnail_id' === $key && 'tribe_events' === get_post_type( $id ) && ! doing_action( 'save_post_tribe_events' ) ) {
		$map = (array) get_option( 'awd_ev_covers', array() );
		if ( ! in_array( (int) get_post_thumbnail_id( $id ), array_map( 'intval', $map ), true ) ) {
			delete_post_meta( $id, '_awd_auto_cover' );
		}
	}
}, 10, 3 );

/* One-time: covers for the events already on the site. */
add_action( 'init', function () {
	if ( (int) get_option( 'awd_ev_extras_v' ) >= 1 || get_transient( 'awd_ev_extras_lock' ) || ! post_type_exists( 'tribe_events' ) ) {
		return;
	}
	set_transient( 'awd_ev_extras_lock', 1, 10 * MINUTE_IN_SECONDS );
	update_option( 'awd_ev_extras_v', 1, false );
	$ids = get_posts( array( 'post_type' => 'tribe_events', 'post_status' => array( 'publish', 'pending', 'draft', 'future' ), 'fields' => 'ids', 'posts_per_page' => 300, 'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'NOT EXISTS' ) ) ) );
	foreach ( $ids as $id ) {
		awd_ev_set_cover( $id );
	}
	delete_transient( 'awd_ev_extras_lock' );
}, 40 );

/* =========================================================================
 * 2. Joining link (Zoom / Meet / Teams) — visible to signed-in members only.
 * ====================================================================== */

function awd_ev_clean_join_link( $url ) {
	$url = esc_url_raw( trim( (string) $url ), array( 'https', 'http' ) );
	return $url && preg_match( '#^https?://#', $url ) ? $url : '';
}

add_action( 'add_meta_boxes_tribe_events', function () {
	add_meta_box( 'awd-join', 'Online joining link (members only)', function ( $post ) {
		wp_nonce_field( 'awd_join_' . $post->ID, 'awd_join_nonce' );
		printf(
			'<p><input type="url" name="awd_join_link" value="%s" placeholder="https://zoom.us/j/…" style="width:100%%"></p><p class="description">Zoom, Google Meet, Teams… Shown on the event page only to signed-in members; visitors are asked to log in or join free.</p>',
			esc_attr( get_post_meta( $post->ID, '_awd_online_link', true ) )
		);
	}, 'tribe_events', 'side' );
} );

add_action( 'save_post_tribe_events', function ( $id ) {
	if ( ! isset( $_POST['awd_join_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_join_nonce'] ) ), 'awd_join_' . $id ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$url = awd_ev_clean_join_link( wp_unslash( $_POST['awd_join_link'] ?? '' ) );
	$url ? update_post_meta( $id, '_awd_online_link', $url ) : delete_post_meta( $id, '_awd_online_link' );
}, 10 );

/* Joining-link box on the event page. */
function awd_ev_join_box( $id, $past ) {
	$url = get_post_meta( $id, '_awd_online_link', true );
	if ( ! $url || $past ) {
		return '';
	}
	$host = preg_match( '#zoom\.#i', $url ) ? 'Zoom' : ( preg_match( '#meet\.google#i', $url ) ? 'Google Meet' : ( preg_match( '#teams\.(microsoft|live)#i', $url ) ? 'Microsoft Teams' : __( 'online session', 'awenly-directory' ) ) );
	ob_start();
	echo '<div class="awd-event__box awd-event__join">';
	echo '<div class="awd-event__row">' . awd_icon( 'video' ) . '<div><strong>' . esc_html__( 'Join online', 'awenly-directory' ) . '</strong><span>' . esc_html( sprintf( __( 'This event runs on %s.', 'awenly-directory' ), $host ) ) . '</span></div></div>'; // phpcs:ignore
	if ( is_user_logged_in() ) {
		echo '<a class="awd-btn awd-event__book" href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( sprintf( __( 'Join on %s', 'awenly-directory' ), $host ) ) . '</a>';
		echo '<p class="awd-event__join-note">' . esc_html__( 'Please keep this link to yourself — it is shared with Awenly Circle members only.', 'awenly-directory' ) . '</p>';
	} else {
		$here = get_permalink( $id );
		echo '<p class="awd-event__join-note">' . esc_html__( 'The joining link is shared with Awenly Circle members. Joining is free.', 'awenly-directory' ) . '</p>';
		echo '<a class="awd-btn awd-event__book" href="' . esc_url( wp_login_url( $here ) ) . '">' . esc_html__( 'Log in to see the link', 'awenly-directory' ) . '</a>';
		if ( get_option( 'users_can_register' ) || function_exists( 'bp_get_signup_page' ) ) {
			$reg = function_exists( 'bp_get_signup_page' ) ? bp_get_signup_page() : wp_registration_url();
			echo '<a class="awd-btn awd-btn--ghost awd-event__book" href="' . esc_url( $reg ) . '">' . esc_html__( 'Join free', 'awenly-directory' ) . '</a>';
		}
	}
	echo '</div>';
	return ob_get_clean();
}

/* =========================================================================
 * 3. Share buttons.
 * ====================================================================== */

function awd_ev_share_links( $title, $url ) {
	$u = rawurlencode( $url );
	$t = rawurlencode( $title );
	return array(
		'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $u,
		'WhatsApp' => 'https://wa.me/?text=' . rawurlencode( $title . ' ' ) . $u,
		'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u,
		'X'        => 'https://twitter.com/intent/tweet?text=' . $t . '&url=' . $u,
		'Telegram' => 'https://t.me/share/url?url=' . $u . '&text=' . $t,
		'Pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $u . '&description=' . $t,
		'E-mail'   => 'mailto:?subject=' . $t . '&body=' . rawurlencode( $title . "\n\n" ) . $u,
	);
}

function awd_ev_share_menu( $title, $url ) {
	$out = '<details class="awd-event__cal awd-event__share"><summary class="awd-btn awd-btn--ghost">' . esc_html__( 'Share', 'awenly-directory' ) . '</summary><div>';
	$out .= '<button type="button" class="awd-share-native" data-title="' . esc_attr( $title ) . '" data-url="' . esc_url( $url ) . '" hidden>' . esc_html__( 'Share via…', 'awenly-directory' ) . '</button>';
	foreach ( awd_ev_share_links( $title, $url ) as $label => $href ) {
		$out .= '<a href="' . esc_url( $href ) . '"' . ( 0 === strpos( $href, 'mailto:' ) ? '' : ' target="_blank" rel="noopener"' ) . '>' . esc_html( $label ) . '</a>';
	}
	$out .= '<button type="button" class="awd-share-copy" data-url="' . esc_url( $url ) . '" data-done="' . esc_attr__( 'Link copied ✓', 'awenly-directory' ) . '">' . esc_html__( 'Copy link', 'awenly-directory' ) . '</button>';
	return $out . '</div></details>';
}

function awd_ev_share_bar( $title, $url ) {
	$out = '<div class="awd-sharebar"><strong>' . esc_html__( 'Share this event', 'awenly-directory' ) . '</strong><div>';
	foreach ( array_slice( awd_ev_share_links( $title, $url ), 0, 4, true ) as $label => $href ) {
		$out .= '<a class="awd-sharebar__btn awd-sharebar__btn--' . esc_attr( strtolower( $label ) ) . '" href="' . esc_url( $href ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
	}
	$out .= '<button type="button" class="awd-sharebar__btn awd-share-copy" data-url="' . esc_url( $url ) . '" data-done="' . esc_attr__( 'Copied ✓', 'awenly-directory' ) . '">' . esc_html__( 'Copy link', 'awenly-directory' ) . '</button>';
	$out .= '<button type="button" class="awd-sharebar__btn awd-share-native" data-title="' . esc_attr( $title ) . '" data-url="' . esc_url( $url ) . '" hidden>' . esc_html__( 'More…', 'awenly-directory' ) . '</button>';
	return $out . '</div></div>';
}

add_action( 'wp_footer', function () {
	if ( ! is_singular( 'tribe_events' ) ) {
		return;
	}
	?>
	<script>
	(function(){
		document.querySelectorAll('.awd-share-native').forEach(function(b){
			if (navigator.share) { b.hidden = false; }
			b.addEventListener('click', function(){ navigator.share({ title: b.dataset.title, url: b.dataset.url }).catch(function(){}); });
		});
		document.querySelectorAll('.awd-share-copy').forEach(function(b){
			b.addEventListener('click', function(){
				var done = function(){ var t = b.textContent; b.textContent = b.dataset.done; setTimeout(function(){ b.textContent = t; }, 1800); };
				if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.url).then(done); }
				else { var i = document.createElement('input'); i.value = b.dataset.url; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); done(); }
			});
		});
	})();
	</script>
	<?php
} );

/* =========================================================================
 * 4. Local time — events keep their own time zone; show the visitor-side time too.
 * ====================================================================== */

/** array( start DateTimeImmutable, end DateTimeImmutable, DateTimeZone event tz ) */
function awd_ev_times( $id ) {
	$tzs = get_post_meta( $id, '_EventTimezone', true );
	try {
		$tz = new DateTimeZone( $tzs ? $tzs : wp_timezone_string() );
	} catch ( Exception $e ) {
		$tz = wp_timezone();
	}
	$s = get_post_meta( $id, '_EventStartDate', true );
	$e = get_post_meta( $id, '_EventEndDate', true );
	$start = new DateTimeImmutable( $s ? $s : 'now', $tz );
	$end   = new DateTimeImmutable( $e ? $e : $start->format( 'Y-m-d H:i:s' ), $tz );
	return array( $start, $end, $tz );
}

/** " · 00:00 Irish time" when the event's zone differs from the site's. */
function awd_ev_home_time( DateTimeImmutable $start, DateTimeZone $tz ) {
	$site = wp_timezone();
	if ( $start->getOffset() === $start->setTimezone( $site )->getOffset() ) {
		return '';
	}
	$local = $start->setTimezone( $site );
	$city  = str_replace( '_', ' ', substr( strrchr( wp_timezone_string(), '/' ) ?: '/', 1 ) );
	$label = 'Dublin' === $city ? __( 'Irish time', 'awenly-directory' ) : sprintf( __( '%s time', 'awenly-directory' ), $city );
	$day   = $local->format( 'Ymd' ) !== $start->format( 'Ymd' ) ? $local->format( 'D ' ) : '';
	return $day . $local->format( 'H:i' ) . ' ' . $label;
}

/* =========================================================================
 * 5. Structured data — complete Event markup for Google's event results.
 * ====================================================================== */

function awd_ev_price( $cost ) {
	$cost = trim( wp_strip_all_tags( (string) $cost ) );
	if ( '' === $cost ) {
		return null;
	}
	if ( preg_match( '/^(free|0|€0|£0|\$0)$/i', $cost ) ) {
		return array( '0', 'EUR' );
	}
	$cur = false !== strpos( $cost, '£' ) || stripos( $cost, 'gbp' ) !== false ? 'GBP' : ( false !== strpos( $cost, '$' ) || stripos( $cost, 'usd' ) !== false ? 'USD' : 'EUR' );
	return preg_match( '/(\d+(?:[.,]\d{1,2})?)/', $cost, $m ) ? array( str_replace( ',', '.', $m[1] ), $cur ) : null;
}

add_filter( 'tribe_json_ld_event_object', function ( $data, $args, $post ) {
	if ( ! is_object( $data ) || empty( $post->ID ) ) {
		return $data;
	}
	$id     = $post->ID;
	$where  = (string) get_post_meta( $id, '_awenly_event_location', true );
	$join   = get_post_meta( $id, '_awd_online_link', true );
	$online = $join || preg_match( '/\b(online|zoom|virtual|webinar|teams|google meet)\b/i', $where );
	$url    = get_permalink( $id );

	$data->eventStatus = 'https://schema.org/EventScheduled';
	$data->eventAttendanceMode = $online ? 'https://schema.org/OnlineEventAttendanceMode' : 'https://schema.org/OfflineEventAttendanceMode';

	if ( empty( $data->image ) && has_post_thumbnail( $id ) ) {
		$data->image = get_the_post_thumbnail_url( $id, 'full' );
	}
	if ( empty( $data->description ) ) {
		$data->description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 );
	}
	if ( $online ) {
		$data->location = (object) array( '@type' => 'VirtualLocation', 'url' => $url );
	} elseif ( empty( $data->location ) && $where ) {
		$parts   = array_values( array_filter( array_map( 'trim', explode( ',', wp_strip_all_tags( $where ) ) ) ) );
		$codes   = array( 'ireland' => 'IE', 'united kingdom' => 'GB', 'uk' => 'GB', 'england' => 'GB', 'scotland' => 'GB', 'wales' => 'GB', 'northern ireland' => 'GB', 'united states' => 'US', 'usa' => 'US', 'canada' => 'CA', 'australia' => 'AU', 'poland' => 'PL' );
		$country = '';
		if ( count( $parts ) > 1 && isset( $codes[ strtolower( end( $parts ) ) ] ) ) {
			$country = $codes[ strtolower( array_pop( $parts ) ) ];
		}
		$address = array( '@type' => 'PostalAddress', 'addressLocality' => count( $parts ) > 1 ? end( $parts ) : $parts[0] );
		if ( count( $parts ) > 2 ) {
			$address['streetAddress'] = implode( ', ', array_slice( $parts, 1, -1 ) );
		}
		if ( $country ) {
			$address['addressCountry'] = $country;
		}
		$data->location = (object) array( '@type' => 'Place', 'name' => $parts[0], 'address' => (object) $address );
	}
	if ( empty( $data->offers ) ) {
		$book  = function_exists( 'tribe_get_event_website_url' ) ? tribe_get_event_website_url( $id ) : '';
		$offer = array( '@type' => 'Offer', 'url' => $book ? $book : $url, 'availability' => 'https://schema.org/InStock', 'validFrom' => get_post_time( 'c', true, $id ) );
		$price = awd_ev_price( function_exists( 'tribe_get_cost' ) ? tribe_get_cost( $id ) : '' );
		if ( $price ) {
			$offer['price']         = $price[0];
			$offer['priceCurrency'] = $price[1];
		}
		$data->offers = (object) $offer;
	}
	if ( empty( $data->organizer ) ) {
		$listing = (int) get_post_meta( $id, '_awd_listing', true );
		$author  = get_post_meta( $id, '_awd_member_event', true ) ? get_userdata( (int) $post->post_author ) : null;
		if ( $listing && 'publish' === get_post_status( $listing ) ) {
			$data->organizer = (object) array( '@type' => 'Person', 'name' => get_the_title( $listing ), 'url' => get_permalink( $listing ) );
		} elseif ( $author ) {
			$data->organizer = (object) array( '@type' => 'Person', 'name' => $author->display_name );
		} else {
			$book = function_exists( 'tribe_get_event_website_url' ) ? tribe_get_event_website_url( $id ) : '';
			$host = $book ? wp_parse_url( $book, PHP_URL_HOST ) : '';
			$data->organizer = (object) array( '@type' => 'Organization', 'name' => $host && false === strpos( $host, 'google.' ) ? preg_replace( '/^www\./', '', $host ) : get_bloginfo( 'name' ), 'url' => $book && false === strpos( (string) $host, 'google.' ) ? $book : home_url( '/' ) );
		}
	}
	if ( empty( $data->performer ) && ! empty( $data->organizer->name ) ) {
		$data->performer = (object) array( '@type' => 'PerformingGroup', 'name' => $data->organizer->name );
	}
	return $data;
}, 20, 3 );

/* Meta description for events that do not have one: date, place and a short summary. */
add_filter( 'rank_math/frontend/description', function ( $desc ) {
	if ( ! is_singular( 'tribe_events' ) || $desc ) {
		return $desc;
	}
	$id                   = get_queried_object_id();
	list( $start, , $tz ) = awd_ev_times( $id );
	$where                = get_post_meta( $id, '_awenly_event_location', true );
	$lead                 = $start->format( 'D j M Y, H:i' ) . ( $where ? ' · ' . $where : '' ) . '. ';
	return mb_substr( $lead . wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 22, '…' ), 0, 158 );
} );
