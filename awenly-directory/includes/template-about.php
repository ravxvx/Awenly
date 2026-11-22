<?php
/**
 * About page template (replaces the old Elementor layout of /about/).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$stats     = awd_about_stats();
$community = function_exists( 'bp_get_activity_directory_permalink' ) ? bp_get_activity_directory_permalink() : home_url( '/' );
$groups    = function_exists( 'bp_get_groups_directory_permalink' ) ? bp_get_groups_directory_permalink() : home_url( '/groups/' );
$events    = function_exists( 'awd_events_list_url' ) ? awd_events_list_url() : home_url( '/events/' );
$join      = is_user_logged_in() ? $community : wp_registration_url();
$offer     = array(
	array( 'user', __( 'Find a practitioner', 'awenly-directory' ), __( 'Search holistic and wellbeing practitioners by therapy, town or online sessions, read reviews and send an enquiry directly.', 'awenly-directory' ), awd_directory_url(), __( 'Browse practitioners', 'awenly-directory' ) ),
	array( 'cal', __( 'Join events', 'awenly-directory' ), __( 'Workshops, circles, retreats and online sessions hosted by members of the circle.', 'awenly-directory' ), $events, __( 'See upcoming events', 'awenly-directory' ) ),
	array( 'users', __( 'Connect with the community', 'awenly-directory' ), __( 'Share your journey in the activity feed, join groups around the practices you love and ask questions in the forums.', 'awenly-directory' ), $community, __( 'Open the community', 'awenly-directory' ) ),
	array( 'book', __( 'Keep learning', 'awenly-directory' ), __( 'Articles and videos from practitioners and members about holistic living and wellbeing.', 'awenly-directory' ), ( get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/blog/' ) ), __( 'Read the blog', 'awenly-directory' ) ),
);
$values = array(
	array( 'sun', __( 'Welcoming', 'awenly-directory' ), __( 'Whether you are just curious or have practised for years, there is a place for you here.', 'awenly-directory' ) ),
	array( 'cert', __( 'Trust first', 'awenly-directory' ), __( 'Every practitioner listing and every event is checked by our team before it goes live, and reviews come from members.', 'awenly-directory' ) ),
	array( 'door', __( 'Free to take part', 'awenly-directory' ), __( 'Joining the community, listing your practice and adding your events is free.', 'awenly-directory' ) ),
	array( 'msg', __( 'Real connection', 'awenly-directory' ), __( 'Talk directly to practitioners and members: no middlemen and no booking commission.', 'awenly-directory' ) ),
);
?>
<div class="awd-directory awd-about-page">
	<section class="awd-hero awd-about-hero">
		<div class="awd-container">
			<span class="awd-kicker"><?php esc_html_e( 'About Awenly Circle', 'awenly-directory' ); ?></span>
			<h1><?php esc_html_e( 'A home for holistic wellbeing', 'awenly-directory' ); ?></h1>
			<p><?php esc_html_e( 'Awenly Circle brings together people who care about mind, body and spirit, and the practitioners who support them. Find someone to work with, join an event, or simply connect with people on the same path.', 'awenly-directory' ); ?></p>
			<div class="awd-ev-actions">
				<a class="awd-btn" href="<?php echo esc_url( $join ); ?>"><?php echo esc_html( is_user_logged_in() ? __( 'Go to the community', 'awenly-directory' ) : __( 'Join the circle - it’s free', 'awenly-directory' ) ); ?></a>
				<a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( awd_directory_url() ); ?>"><?php esc_html_e( 'Find a practitioner', 'awenly-directory' ); ?></a>
			</div>
		</div>
	</section>

	<section class="awd-container">
		<div class="awd-about-stats">
			<?php foreach ( array( array( $stats['members'], _n( 'member', 'members', $stats['members'], 'awenly-directory' ) ), array( $stats['practitioners'], _n( 'practitioner listed', 'practitioners listed', $stats['practitioners'], 'awenly-directory' ) ), array( $stats['events'], _n( 'upcoming event', 'upcoming events', $stats['events'], 'awenly-directory' ) ), array( $stats['groups'], _n( 'community group', 'community groups', $stats['groups'], 'awenly-directory' ) ) ) as $st ) :
				if ( $st[0] < 1 ) { continue; } ?>
				<div><b><?php echo esc_html( number_format_i18n( $st[0] ) ); ?></b><span><?php echo esc_html( $st[1] ); ?></span></div>
			<?php endforeach; ?>
		</div>

		<div class="awd-about-block">
			<span class="awd-kicker"><?php esc_html_e( 'What you can do here', 'awenly-directory' ); ?></span>
			<h2><?php esc_html_e( 'Everything in one circle', 'awenly-directory' ); ?></h2>
			<div class="awd-about-grid">
				<?php foreach ( $offer as $o ) : ?>
					<a class="awd-contact-card" href="<?php echo esc_url( $o[3] ); ?>">
						<span class="awd-contact-card__icon"><?php echo awd_icon( $o[0] ); // phpcs:ignore ?></span>
						<span class="awd-contact-card__body"><strong><?php echo esc_html( $o[1] ); ?></strong><small><?php echo esc_html( $o[2] ); ?></small><em><?php echo esc_html( $o[4] ); ?> →</em></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="awd-about-block awd-about-split">
			<div>
				<span class="awd-kicker"><?php esc_html_e( 'For practitioners', 'awenly-directory' ); ?></span>
				<h2><?php esc_html_e( 'Grow your practice with a free listing', 'awenly-directory' ); ?></h2>
				<p><?php esc_html_e( 'Therapists, coaches, healers, teachers and wellness businesses can create a listing from their profile in a few minutes.', 'awenly-directory' ); ?></p>
				<ul class="awd-about-list">
					<li><?php esc_html_e( 'A professional page with your services, photos, video and reviews', 'awenly-directory' ); ?></li>
					<li><?php esc_html_e( 'Enquiries straight to your inbox, and a place on the directory map', 'awenly-directory' ); ?></li>
					<li><?php esc_html_e( 'Your workshops and sessions in the events calendar', 'awenly-directory' ); ?></li>
					<li><?php esc_html_e( 'Statistics on views and clicks, so you see what works', 'awenly-directory' ); ?></li>
				</ul>
				<a class="awd-btn" href="<?php echo esc_url( is_user_logged_in() && function_exists( 'awd_practice_url' ) ? awd_practice_url() : wp_registration_url() ); ?>"><?php esc_html_e( 'List your practice', 'awenly-directory' ); ?></a>
			</div>
			<div class="awd-about-values">
				<?php foreach ( $values as $v ) : ?>
					<div class="awd-about-value"><span class="awd-contact-card__icon"><?php echo awd_icon( $v[0] ); // phpcs:ignore ?></span><div><strong><?php echo esc_html( $v[1] ); ?></strong><p><?php echo esc_html( $v[2] ); ?></p></div></div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="awd-cta">
			<div><h2><?php esc_html_e( 'Questions or ideas?', 'awenly-directory' ); ?></h2><p><?php esc_html_e( 'We would love to hear from you, whether you are a member, a practitioner or a partner.', 'awenly-directory' ); ?></p></div>
			<a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact us', 'awenly-directory' ); ?></a>
		</div>
	</section>
</div>
<?php
get_footer();
