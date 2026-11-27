<?php
/**
 * Welcome banner at the top of the Activity Feed (/news-feed/): stone-circle artwork,
 * greeting, community numbers and quick actions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is this the main activity directory (not a member's or group's activity tab)?
 */
function awenly_is_activity_directory() {
	return function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory();
}

/**
 * Cached community numbers (members, groups, upcoming events).
 */
function awenly_community_stats() {
	$stats = get_transient( 'awenly_community_stats' );
	if ( is_array( $stats ) ) {
		return $stats;
	}
	$stats = array( 'members' => 0, 'groups' => 0, 'events' => 0 );
	if ( function_exists( 'bp_get_total_member_count' ) ) {
		$stats['members'] = (int) bp_get_total_member_count();
	}
	if ( function_exists( 'groups_get_total_group_count' ) ) {
		$stats['groups'] = (int) groups_get_total_group_count();
	}
	if ( function_exists( 'tribe_get_events' ) ) {
		$ids             = tribe_get_events( array( 'start_date' => 'now', 'posts_per_page' => 200, 'fields' => 'ids' ) );
		$stats['events'] = is_array( $ids ) ? count( $ids ) : 0;
	}
	set_transient( 'awenly_community_stats', $stats, HOUR_IN_SECONDS );
	return $stats;
}

/**
 * Print the banner once, before the activity directory content.
 */
function awenly_activity_banner() {
	static $done = false;
	if ( $done || ! awenly_is_activity_directory() ) {
		return;
	}
	$done = true;

	$logged = is_user_logged_in();
	$name   = $logged ? wp_get_current_user()->first_name : '';
	if ( $logged && ! $name ) {
		$name = wp_get_current_user()->display_name;
	}
	$s      = awenly_community_stats();
	$events = awenly_opt( 'events_url' ) ? awenly_opt( 'events_url' ) : home_url( '/events/' );
	$dir    = awenly_opt( 'practitioners_url' ) ? awenly_opt( 'practitioners_url' ) : home_url( '/practitioners/' );
	$join   = awenly_opt( 'join_url' ) ? awenly_opt( 'join_url' ) : wp_registration_url();
	?>
	<section class="awa-banner" aria-labelledby="awa-title">
		<div class="awa-art"><?php echo awenly_stone_circle_art(); // phpcs:ignore ?></div>
		<div class="awa-inner">
			<span class="awa-kicker"><?php esc_html_e( 'Community', 'awenly-child' ); ?></span>
			<h2 id="awa-title">
				<?php
				if ( $logged ) {
					/* translators: %s: member first name */
					printf( esc_html__( 'Welcome back, %s', 'awenly-child' ), esc_html( $name ) );
				} else {
					esc_html_e( 'Welcome to the circle', 'awenly-child' );
				}
				?>
			</h2>
			<p><?php esc_html_e( 'Share your journey, ask questions and support each other. A gentle space for healing, learning and growing together.', 'awenly-child' ); ?></p>
			<div class="awa-actions">
				<?php if ( $logged ) : ?>
					<a class="awa-btn awa-btn--lime" href="#whats-new-form" data-awa-post>✍️ <?php esc_html_e( 'Share an update', 'awenly-child' ); ?></a>
				<?php else : ?>
					<a class="awa-btn awa-btn--lime" href="<?php echo esc_url( $join ); ?>"><?php esc_html_e( 'Join Awenly Circle', 'awenly-child' ); ?></a>
				<?php endif; ?>
				<a class="awa-btn" href="<?php echo esc_url( $events ); ?>"><?php esc_html_e( 'Upcoming events', 'awenly-child' ); ?></a>
				<a class="awa-btn" href="<?php echo esc_url( $dir ); ?>"><?php esc_html_e( 'Find a practitioner', 'awenly-child' ); ?></a>
			</div>
			<ul class="awa-stats">
				<?php if ( $s['members'] ) : ?><li><b><?php echo esc_html( number_format_i18n( $s['members'] ) ); ?></b><span><?php esc_html_e( 'members', 'awenly-child' ); ?></span></li><?php endif; ?>
				<?php if ( $s['groups'] ) : ?><li><b><?php echo esc_html( number_format_i18n( $s['groups'] ) ); ?></b><span><?php esc_html_e( 'groups', 'awenly-child' ); ?></span></li><?php endif; ?>
				<?php if ( $s['events'] ) : ?><li><b><?php echo esc_html( number_format_i18n( $s['events'] ) ); ?></b><span><?php esc_html_e( 'upcoming events', 'awenly-child' ); ?></span></li><?php endif; ?>
			</ul>
		</div>
	</section>
	<?php
}
add_action( 'bp_before_directory_activity', 'awenly_activity_banner', 5 );
add_action( 'bp_before_directory_activity_content', 'awenly_activity_banner', 5 );

/**
 * Body class so CSS can hide the plain page title (the banner replaces it).
 */
add_filter( 'body_class', function ( $classes ) {
	if ( awenly_is_activity_directory() ) {
		$classes[] = 'awenly-activity-dir';
	}
	return $classes;
} );

/**
 * "Share an update" opens the post box instead of just jumping to it.
 */
add_action( 'wp_footer', function () {
	if ( ! awenly_is_activity_directory() || ! is_user_logged_in() ) {
		return;
	}
	?>
	<script>
	document.addEventListener('click', function (e) {
		var a = e.target.closest('[data-awa-post]');
		if (!a) { return; }
		e.preventDefault();
		var box = document.querySelector('#whats-new-form, #bp-nouveau-activity-form');
		if (!box) { return; }
		box.scrollIntoView({ behavior: 'smooth', block: 'center' });
		var t = box.querySelector('#whats-new, [contenteditable="true"], textarea');
		if (t) { setTimeout(function () { t.click(); t.focus(); }, 450); }
	});
	</script>
	<?php
}, 30 );
