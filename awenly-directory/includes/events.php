<?php
/**
 * Member events: any signed-in member can submit an event (The Events Calendar), which waits for admin approval.
 * Shortcodes: [awenly_event_form] (page /add-events/), [awenly_my_events] (page /my-events/), [awenly_events_hub].
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AWD_EV_MAX_PENDING = 5;

function awd_add_event_url() {
	$p = get_page_by_path( 'add-events' );
	return $p ? get_permalink( $p ) : home_url( '/add-events/' );
}

function awd_my_events_url() {
	$p = get_page_by_path( 'my-events' );
	return $p ? get_permalink( $p ) : home_url( '/my-events/' );
}

function awd_events_list_url() {
	return function_exists( 'tribe_get_events_link' ) ? tribe_get_events_link() : home_url( '/events/' );
}

/**
 * Creates a pending event. $d: title, date, start, end, where, mode (online|in-person), link, desc, cat. Returns post ID or WP_Error.
 */
function awd_create_member_event( $user_id, $d ) {
	if ( ! awd_events_active() || ! function_exists( 'tribe_create_event' ) ) {
		return new WP_Error( 'off', __( 'Events are not available right now.', 'awenly-directory' ) );
	}
	$pending = get_posts( array( 'post_type' => 'tribe_events', 'author' => $user_id, 'post_status' => 'pending', 'fields' => 'ids', 'posts_per_page' => AWD_EV_MAX_PENDING + 1 ) );
	if ( count( $pending ) >= AWD_EV_MAX_PENDING && ! user_can( $user_id, 'edit_others_posts' ) ) {
		return new WP_Error( 'many', __( 'You already have 5 events waiting for approval. Please wait until they are reviewed.', 'awenly-directory' ) );
	}
	$start_ts = strtotime( $d['date'] . ' ' . $d['start'] );
	if ( ! $d['title'] || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['date'] ) || ! preg_match( '/^\d{2}:\d{2}$/', $d['start'] ) || ! $start_ts || $start_ts < time() ) {
		return new WP_Error( 'invalid', __( 'Please give the event a title and a date and start time in the future.', 'awenly-directory' ) );
	}
	$end_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d['end_date'] ?? '' ) && $d['end_date'] >= $d['date'] ? $d['end_date'] : $d['date'];
	$end      = $d['end'];
	if ( ! preg_match( '/^\d{2}:\d{2}$/', $end ) || strtotime( $end_date . ' ' . $end ) <= $start_ts ) {
		$end_date = gmdate( 'Y-m-d', $start_ts + HOUR_IN_SECONDS );
		$end      = gmdate( 'H:i', $start_ts + HOUR_IN_SECONDS );
	}
	$where = 'online' === $d['mode'] ? __( 'Online', 'awenly-directory' ) . ( $d['where'] ? ' · ' . $d['where'] : '' ) : $d['where'];
	$desc  = $d['desc'];
	if ( $where ) {
		$desc .= "\n\n<p><strong>" . esc_html__( 'Where:', 'awenly-directory' ) . '</strong> ' . esc_html( $where ) . '</p>';
	}
	$event_id = tribe_create_event( array(
		'post_title'       => mb_substr( $d['title'], 0, 140 ),
		'post_content'     => $desc,
		'post_status'      => 'pending',
		'post_author'      => $user_id,
		'EventStartDate'   => $d['date'],
		'EventEndDate'     => $end_date,
		'EventStartHour'   => substr( $d['start'], 0, 2 ),
		'EventStartMinute' => substr( $d['start'], 3, 2 ),
		'EventEndHour'     => substr( $end, 0, 2 ),
		'EventEndMinute'   => substr( $end, 3, 2 ),
		'EventURL'         => $d['link'],
		'EventCost'        => $d['cost'] ?? '',
	) );
	if ( ! $event_id ) {
		return new WP_Error( 'fail', __( 'The event could not be saved. Please try again.', 'awenly-directory' ) );
	}
	update_post_meta( $event_id, '_awenly_event_location', $where );
	update_post_meta( $event_id, '_awd_member_event', 1 );
	$pub = function_exists( 'awd_get_user_listings' ) ? awd_get_user_listings( $user_id, true ) : array();
	if ( $pub ) {
		update_post_meta( $event_id, '_awd_listing', $pub[0]->ID );
	}
	if ( ! empty( $d['cat'] ) && taxonomy_exists( 'tribe_events_cat' ) && term_exists( (int) $d['cat'], 'tribe_events_cat' ) ) {
		wp_set_object_terms( $event_id, array( (int) $d['cat'] ), 'tribe_events_cat' );
	}
	$user = get_userdata( $user_id );
	wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[%s] New event to approve: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $d['title'] ),
		sprintf( "%s submitted an event (%s %s, %s).\n\nReview and publish it here:\n%s\n", $user ? $user->display_name : '#' . $user_id, $d['date'], $d['start'], $where ? $where : '-', admin_url( 'post.php?post=' . $event_id . '&action=edit' ) )
	);
	return $event_id;
}

/** Optional cover image from the form field awd_ev_image (jpg/png/webp, max 5 MB). */
function awd_event_attach_image( $event_id ) {
	if ( empty( $_FILES['awd_ev_image']['name'] ) || ! empty( $_FILES['awd_ev_image']['error'] ) ) {
		return;
	}
	if ( (int) $_FILES['awd_ev_image']['size'] > 5 * MB_IN_BYTES ) {
		return;
	}
	$type = wp_check_filetype_and_ext( $_FILES['awd_ev_image']['tmp_name'], $_FILES['awd_ev_image']['name'] );
	if ( ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$att = media_handle_upload( 'awd_ev_image', $event_id );
	if ( ! is_wp_error( $att ) ) {
		set_post_thumbnail( $event_id, $att );
	}
}

/* ---------- form handler ---------- */
add_action( 'init', function () {
	if ( empty( $_POST['awd_action'] ) || 'member_event' !== $_POST['awd_action'] ) {
		return;
	}
	$back = remove_query_arg( array( 'awd_ev' ), wp_get_referer() ? wp_get_referer() : awd_add_event_url() );
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( $back ) );
		exit;
	}
	$user_id = get_current_user_id();
	if ( ! isset( $_POST['awd_mnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_mnonce'] ) ), 'awd_member_event_' . $user_id ) ) {
		wp_die( esc_html__( 'Your session expired. Please go back and try again.', 'awenly-directory' ) );
	}
	if ( ! empty( $_POST['awd_ev_website'] ) ) { // honeypot
		wp_safe_redirect( add_query_arg( 'awd_ev', 'sent', $back ) );
		exit;
	}
	$f = function ( $k ) {
		return sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) );
	};
	$res = awd_create_member_event( $user_id, array(
		'title'    => $f( 'awd_ev_title' ),
		'date'     => $f( 'awd_ev_date' ),
		'end_date' => $f( 'awd_ev_end_date' ),
		'start'    => $f( 'awd_ev_start' ),
		'end'      => $f( 'awd_ev_end' ),
		'mode'     => 'online' === $f( 'awd_ev_mode' ) ? 'online' : 'in-person',
		'where'    => $f( 'awd_ev_where' ),
		'link'     => esc_url_raw( wp_unslash( $_POST['awd_ev_link'] ?? '' ), array( 'http', 'https' ) ),
		'cost'     => mb_substr( $f( 'awd_ev_cost' ), 0, 40 ),
		'cat'      => absint( $_POST['awd_ev_cat'] ?? 0 ),
		'desc'     => wp_kses_post( wp_unslash( $_POST['awd_ev_desc'] ?? '' ) ),
	) );
	if ( is_wp_error( $res ) ) {
		set_transient( 'awd_ev_err_' . $user_id, $res->get_error_message(), 300 );
		wp_safe_redirect( add_query_arg( 'awd_ev', 'error', $back ) );
		exit;
	}
	$join = function_exists( 'awd_ev_clean_join_link' ) ? awd_ev_clean_join_link( wp_unslash( $_POST['awd_ev_join'] ?? '' ) ) : '';
	if ( $join ) {
		update_post_meta( $res, '_awd_online_link', $join );
	}
	awd_event_attach_image( $res );
	wp_safe_redirect( add_query_arg( 'awd_ev', 'sent', awd_my_events_url() ) );
	exit;
} );

/* Withdraw your own event while it is still pending. */
add_action( 'init', function () {
	if ( empty( $_POST['awd_action'] ) || 'withdraw_event' !== $_POST['awd_action'] || ! is_user_logged_in() ) {
		return;
	}
	$id = absint( $_POST['awd_ev_id'] ?? 0 );
	$ev = get_post( $id );
	if ( ! $ev || 'tribe_events' !== $ev->post_type || (int) $ev->post_author !== get_current_user_id() || 'pending' !== $ev->post_status
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_wnonce'] ?? '' ) ), 'awd_withdraw_' . $id ) ) {
		wp_die( esc_html__( 'This event cannot be withdrawn.', 'awenly-directory' ) );
	}
	wp_trash_post( $id );
	wp_safe_redirect( add_query_arg( 'awd_ev', 'withdrawn', awd_my_events_url() ) );
	exit;
} );

/* Tell the member when their event goes live. */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'tribe_events' !== $post->post_type || 'publish' !== $new || 'pending' !== $old || ! get_post_meta( $post->ID, '_awd_member_event', true ) ) {
		return;
	}
	$user = get_userdata( $post->post_author );
	if ( $user && $user->user_email ) {
		wp_mail( $user->user_email, sprintf( '[%s] Your event is live: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $post->post_title ),
			sprintf( "Good news - your event \"%s\" was approved and is now listed:\n%s\n\nShare the link with your people!\n", $post->post_title, get_permalink( $post ) ) );
	}
}, 10, 3 );

/* ---------- views ---------- */
function awd_events_assets() {
	wp_enqueue_style( 'awenly-directory', AWD_URI . 'assets/directory.css', array(), AWD_VERSION );
}

function awd_ev_notice() {
	$s = sanitize_key( $_GET['awd_ev'] ?? '' );
	if ( 'sent' === $s ) {
		return '<div class="awd-notice awd-notice--ok">' . esc_html__( 'Thank you! Your event was sent for review. We usually approve events within a day, and we email you when it is live.', 'awenly-directory' ) . '</div>';
	}
	if ( 'withdrawn' === $s ) {
		return '<div class="awd-notice awd-notice--ok">' . esc_html__( 'The event was withdrawn.', 'awenly-directory' ) . '</div>';
	}
	if ( 'error' === $s && is_user_logged_in() ) {
		$m = get_transient( 'awd_ev_err_' . get_current_user_id() );
		delete_transient( 'awd_ev_err_' . get_current_user_id() );
		return '<div class="awd-notice awd-notice--err">' . esc_html( $m ? $m : __( 'Something went wrong. Please try again.', 'awenly-directory' ) ) . '</div>';
	}
	return '';
}

function awd_ev_guest_box( $text ) {
	$here = get_permalink();
	return '<div class="awd-ev-guest"><p>' . esc_html( $text ) . '</p><div class="awd-ev-actions"><a class="awd-btn" href="' . esc_url( wp_registration_url() ) . '">' . esc_html__( 'Join free', 'awenly-directory' ) . '</a> <a class="awd-btn awd-btn--ghost" href="' . esc_url( wp_login_url( $here ) ) . '">' . esc_html__( 'Sign in', 'awenly-directory' ) . '</a></div></div>';
}

add_shortcode( 'awenly_event_form', function () {
	awd_events_assets();
	if ( ! awd_events_active() ) {
		return '<p>' . esc_html__( 'Events are not available right now.', 'awenly-directory' ) . '</p>';
	}
	ob_start();
	echo '<div class="awd-wrap awd-ev">' . awd_ev_notice(); // phpcs:ignore
	if ( ! is_user_logged_in() ) {
		echo awd_ev_guest_box( __( 'Sign in or join Awenly Circle (free) to add your workshop, circle, retreat or online session to the events calendar.', 'awenly-directory' ) ); // phpcs:ignore
		echo '</div>';
		return ob_get_clean();
	}
	$uid  = get_current_user_id();
	$cats = taxonomy_exists( 'tribe_events_cat' ) ? get_terms( array( 'taxonomy' => 'tribe_events_cat', 'hide_empty' => false ) ) : array();
	?>
	<div class="awd-ev-head">
		<div><h2><?php esc_html_e( 'Add your event', 'awenly-directory' ); ?></h2><p class="awd-muted"><?php esc_html_e( 'Workshops, circles, retreats, classes or online sessions. Every event is checked by our team before it appears in the calendar.', 'awenly-directory' ); ?></p></div>
		<a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( awd_my_events_url() ); ?>"><?php esc_html_e( 'My events', 'awenly-directory' ); ?></a>
	</div>
	<form class="awd-form awd-ev-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'awd_member_event_' . $uid, 'awd_mnonce' ); ?>
		<input type="hidden" name="awd_action" value="member_event">
		<p class="awd-hp" aria-hidden="true"><label>Website <input type="text" name="awd_ev_website" tabindex="-1" autocomplete="off"></label></p>
		<div class="awd-grid">
			<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Event title *', 'awenly-directory' ); ?></span><input type="text" name="awd_ev_title" required maxlength="140" placeholder="<?php esc_attr_e( 'e.g. Full Moon Sound Bath', 'awenly-directory' ); ?>"></label>
			<label class="awd-field"><span><?php esc_html_e( 'Date *', 'awenly-directory' ); ?></span><input type="date" name="awd_ev_date" required min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
			<label class="awd-field"><span><?php esc_html_e( 'Ends on (for multi-day events)', 'awenly-directory' ); ?></span><input type="date" name="awd_ev_end_date" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
			<label class="awd-field"><span><?php esc_html_e( 'Start time *', 'awenly-directory' ); ?></span><input type="time" name="awd_ev_start" required></label>
			<label class="awd-field"><span><?php esc_html_e( 'End time', 'awenly-directory' ); ?></span><input type="time" name="awd_ev_end"></label>
			<div class="awd-field awd-field--wide"><span><?php esc_html_e( 'Format *', 'awenly-directory' ); ?></span>
				<div class="awd-ev-mode">
					<label><input type="radio" name="awd_ev_mode" value="in-person" checked> <?php esc_html_e( 'In person', 'awenly-directory' ); ?></label>
					<label><input type="radio" name="awd_ev_mode" value="online"> <?php esc_html_e( 'Online', 'awenly-directory' ); ?></label>
				</div>
			</div>
			<label class="awd-field"><span><?php esc_html_e( 'Venue and town (or platform, e.g. Zoom)', 'awenly-directory' ); ?></span><input type="text" name="awd_ev_where" maxlength="120"></label>
			<label class="awd-field"><span><?php esc_html_e( 'Price', 'awenly-directory' ); ?></span><input type="text" name="awd_ev_cost" maxlength="40" placeholder="<?php esc_attr_e( 'e.g. €25 or Free', 'awenly-directory' ); ?>"></label>
			<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
				<label class="awd-field"><span><?php esc_html_e( 'Category', 'awenly-directory' ); ?></span><select name="awd_ev_cat"><option value="0"><?php esc_html_e( '- choose -', 'awenly-directory' ); ?></option><?php foreach ( $cats as $c ) : ?><option value="<?php echo (int) $c->term_id; ?>"><?php echo esc_html( $c->name ); ?></option><?php endforeach; ?></select></label>
			<?php endif; ?>
			<label class="awd-field"><span><?php esc_html_e( 'Booking or info link', 'awenly-directory' ); ?></span><input type="url" name="awd_ev_link" placeholder="https://"></label>
			<label class="awd-field"><span><?php esc_html_e( 'Online joining link (Zoom, Meet, Teams)', 'awenly-directory' ); ?></span><input type="url" name="awd_ev_join" placeholder="https://zoom.us/j/…"><small><?php esc_html_e( 'Only signed-in members will see it.', 'awenly-directory' ); ?></small></label>
			<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Description *', 'awenly-directory' ); ?></span><textarea name="awd_ev_desc" rows="6" required placeholder="<?php esc_attr_e( 'What will happen, who it is for, what to bring...', 'awenly-directory' ); ?>"></textarea></label>
			<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Cover image (JPG, PNG or WebP, max 5 MB)', 'awenly-directory' ); ?></span><input type="file" name="awd_ev_image" accept="image/jpeg,image/png,image/webp"></label>
		</div>
		<button class="awd-btn" type="submit"><?php esc_html_e( 'Submit event for review', 'awenly-directory' ); ?></button>
	</form>
	</div>
	<?php
	return ob_get_clean();
} );

add_shortcode( 'awenly_my_events', function () {
	awd_events_assets();
	ob_start();
	echo '<div class="awd-wrap awd-ev">' . awd_ev_notice(); // phpcs:ignore
	if ( ! is_user_logged_in() ) {
		echo awd_ev_guest_box( __( 'Sign in to see and manage the events you added.', 'awenly-directory' ) ); // phpcs:ignore
		echo '</div>';
		return ob_get_clean();
	}
	$mine = awd_events_active() ? get_posts( array(
		'post_type'      => 'tribe_events',
		'author'         => get_current_user_id(),
		'post_status'    => array( 'publish', 'pending', 'draft' ),
		'posts_per_page' => 50,
		'meta_key'       => '_EventStartDate',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	) ) : array();
	?>
	<div class="awd-ev-head">
		<div><h2><?php esc_html_e( 'My events', 'awenly-directory' ); ?></h2><p class="awd-muted"><?php esc_html_e( 'Events you added. New events wait for approval before they are shown in the calendar.', 'awenly-directory' ); ?></p></div>
		<a class="awd-btn" href="<?php echo esc_url( awd_add_event_url() ); ?>"><?php esc_html_e( '+ Add an event', 'awenly-directory' ); ?></a>
	</div>
	<?php if ( ! $mine ) : ?>
		<div class="awd-ev-empty"><p><?php esc_html_e( 'You have not added any events yet.', 'awenly-directory' ); ?></p></div>
	<?php else : ?>
		<ul class="awd-ev-list">
			<?php foreach ( $mine as $e ) :
				$start = get_post_meta( $e->ID, '_EventStartDate', true );
				$past  = $start && strtotime( $start ) < time();
				if ( 'publish' === $e->post_status ) {
					$st = $past ? array( 'hidden', __( 'Finished', 'awenly-directory' ) ) : array( 'live', __( 'Live', 'awenly-directory' ) );
				} else {
					$st = array( 'pending', __( 'Waiting for approval', 'awenly-directory' ) );
				} ?>
				<li class="awd-ev-item">
					<span class="awd-ev-date"><b><?php echo esc_html( $start ? wp_date( 'd', strtotime( $start ) ) : '' ); ?></b><small><?php echo esc_html( $start ? wp_date( 'M Y', strtotime( $start ) ) : '' ); ?></small></span>
					<span class="awd-ev-main"><strong><?php echo esc_html( get_the_title( $e ) ); ?></strong><small><?php echo esc_html( ( $start ? wp_date( 'D, H:i', strtotime( $start ) ) : '' ) . ( get_post_meta( $e->ID, '_awenly_event_location', true ) ? ' · ' . get_post_meta( $e->ID, '_awenly_event_location', true ) : '' ) ); ?></small></span>
					<span class="awd-status--<?php echo esc_attr( $st[0] ); ?>"><span><?php echo esc_html( $st[1] ); ?></span></span>
					<span class="awd-ev-btns">
						<?php if ( 'publish' === $e->post_status ) : ?><a class="awd-btn awd-btn--sm awd-btn--ghost" href="<?php echo esc_url( get_permalink( $e ) ); ?>"><?php esc_html_e( 'View', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( 'pending' === $e->post_status ) : ?>
							<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Withdraw this event?', 'awenly-directory' ) ); ?>');"><?php wp_nonce_field( 'awd_withdraw_' . $e->ID, 'awd_wnonce' ); ?><input type="hidden" name="awd_action" value="withdraw_event"><input type="hidden" name="awd_ev_id" value="<?php echo (int) $e->ID; ?>"><button class="awd-btn awd-btn--sm awd-btn--ghost" type="submit"><?php esc_html_e( 'Withdraw', 'awenly-directory' ); ?></button></form>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
} );

/* Hub for the old /event-directory/ page: upcoming events + add button. */
add_shortcode( 'awenly_events_hub', function () {
	awd_events_assets();
	$events = awd_events_active() ? tribe_get_events( array( 'posts_per_page' => 6, 'start_date' => 'now', 'eventDisplay' => 'list' ) ) : array();
	ob_start(); ?>
	<div class="awd-wrap awd-ev">
		<div class="awd-ev-banner">
			<div><h2><?php esc_html_e( 'Share your event with the circle', 'awenly-directory' ); ?></h2><p><?php esc_html_e( 'Workshops, circles, retreats and online sessions from our members. Adding an event is free.', 'awenly-directory' ); ?></p></div>
			<div class="awd-ev-actions"><a class="awd-btn" href="<?php echo esc_url( awd_add_event_url() ); ?>"><?php esc_html_e( '+ Add your event', 'awenly-directory' ); ?></a><a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( awd_events_list_url() ); ?>"><?php esc_html_e( 'Full calendar', 'awenly-directory' ); ?></a></div>
		</div>
		<?php if ( $events ) : ?>
			<ul class="awd-ev-list">
				<?php foreach ( $events as $e ) :
					$start = get_post_meta( $e->ID, '_EventStartDate', true ); ?>
					<li class="awd-ev-item">
						<span class="awd-ev-date"><b><?php echo esc_html( wp_date( 'd', strtotime( $start ) ) ); ?></b><small><?php echo esc_html( wp_date( 'M', strtotime( $start ) ) ); ?></small></span>
						<span class="awd-ev-main"><strong><a href="<?php echo esc_url( get_permalink( $e ) ); ?>"><?php echo esc_html( get_the_title( $e ) ); ?></a></strong><small><?php echo esc_html( wp_date( 'D, H:i', strtotime( $start ) ) . ( get_post_meta( $e->ID, '_awenly_event_location', true ) ? ' · ' . get_post_meta( $e->ID, '_awenly_event_location', true ) : '' ) ); ?></small></span>
						<span class="awd-ev-btns"><a class="awd-btn awd-btn--sm awd-btn--ghost" href="<?php echo esc_url( get_permalink( $e ) ); ?>"><?php esc_html_e( 'Details', 'awenly-directory' ); ?></a></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="awd-ev-empty"><p><?php esc_html_e( 'No upcoming events yet. Be the first to add one!', 'awenly-directory' ); ?></p></div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
} );

/* "Add your event" bar above The Events Calendar views (/events/). */
add_action( 'tribe_template_before_include:events/v2/components/events-bar', function () {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	awd_events_assets();
	if ( ! is_singular() ) {
		echo '<h1 class="awd-ev-h1">' . esc_html__( 'Wellbeing events, workshops & retreats', 'awenly-directory' ) . '</h1><p class="awd-ev-lead">' . esc_html__( 'Healing circles, workshops, retreats and online sessions hosted by the Awenly Circle community.', 'awenly-directory' ) . '</p>';
	}
	echo '<div class="awd-ev-bar"><span>' . esc_html__( 'Running a workshop, circle or retreat?', 'awenly-directory' ) . '</span><a class="awd-btn awd-btn--sm" href="' . esc_url( awd_add_event_url() ) . '">' . esc_html__( '+ Add your event', 'awenly-directory' ) . '</a>'
		. ( is_user_logged_in() ? ' <a class="awd-btn awd-btn--sm awd-btn--ghost" href="' . esc_url( awd_my_events_url() ) . '">' . esc_html__( 'My events', 'awenly-directory' ) . '</a>' : '' ) . '</div>'; // phpcs:ignore
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'tribe_is_event_query' ) && tribe_is_event_query() ) {
		awd_events_assets();
	}
}, 10002 );

/* The old event pages were built with Elementor (its layout ignores the page content), so render them ourselves. */
function awd_event_page_shortcode() {
	if ( is_page( 'add-events' ) ) {
		return '[awenly_event_form]';
	}
	if ( is_page( 'my-events' ) ) {
		return '[awenly_my_events]';
	}
	if ( is_page( 'event-directory' ) ) {
		return '[awenly_events_hub]';
	}
	return '';
}

add_filter( 'template_include', function ( $template ) {
	return awd_event_page_shortcode() ? AWD_DIR . 'includes/template-events-page.php' : $template;
}, 100 );

add_filter( 'body_class', function ( $c ) {
	if ( awd_event_page_shortcode() ) {
		$c[] = 'awd-page';
	}
	return $c;
} );

/* Single event page in the Awenly design. */
add_filter( 'template_include', function ( $template ) {
	if ( is_singular( 'tribe_events' ) && function_exists( 'tribe_get_start_date' ) && ! post_password_required() ) {
		return AWD_DIR . 'includes/template-event.php';
	}
	return $template;
}, 200 );

add_filter( 'body_class', function ( $c ) {
	if ( is_singular( 'tribe_events' ) ) {
		$c[] = 'awd-page';
	}
	return $c;
} );
