<?php
/**
 * Contact page template (replaces the Elementor layout of /contact-us/).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$community = function_exists( 'bp_get_activity_directory_permalink' ) ? home_url( '/news-feed/' ) : home_url( '/' );
$events    = function_exists( 'tribe_get_events_link' ) ? tribe_get_events_link() : home_url( '/events/' );
$cards     = array(
	array( 'users', __( 'Ask the community', 'awenly-directory' ), __( 'Members share tips, recommendations and support every day.', 'awenly-directory' ), $community, __( 'Open the community', 'awenly-directory' ) ),
	array( 'user', __( 'Practitioner listings', 'awenly-directory' ), __( 'Questions about adding or editing your free listing.', 'awenly-directory' ), awd_directory_url(), __( 'Browse the directory', 'awenly-directory' ) ),
	array( 'cal', __( 'Events & workshops', 'awenly-directory' ), __( 'Tickets, times and hosting your own event.', 'awenly-directory' ), $events, __( 'See upcoming events', 'awenly-directory' ) ),
);
?>
<div class="awd-directory awd-contact-page">
	<section class="awd-hero awd-contact-hero">
		<div class="awd-container">
			<span class="awd-kicker"><?php esc_html_e( 'Contact', 'awenly-directory' ); ?></span>
			<h1><?php esc_html_e( 'We’re here to help', 'awenly-directory' ); ?></h1>
			<p><?php esc_html_e( 'Questions about membership, listings, events or partnerships? Send us a message and a real person from the Awenly Circle team will get back to you.', 'awenly-directory' ); ?></p>
		</div>
	</section>

	<section class="awd-container awd-contact-grid">
		<aside class="awd-contact-side">
			<?php foreach ( $cards as $c ) : ?>
				<a class="awd-contact-card" href="<?php echo esc_url( $c[3] ); ?>">
					<span class="awd-contact-card__icon"><?php echo awd_icon( $c[0] ); // phpcs:ignore ?></span>
					<span class="awd-contact-card__body"><strong><?php echo esc_html( $c[1] ); ?></strong><small><?php echo esc_html( $c[2] ); ?></small><em><?php echo esc_html( $c[4] ); ?> →</em></span>
				</a>
			<?php endforeach; ?>
			<div class="awd-contact-note">
				<?php echo awd_icon( 'clock' ); // phpcs:ignore ?>
				<p><strong><?php esc_html_e( 'We read every message', 'awenly-directory' ); ?></strong><?php esc_html_e( 'Replies go to the email address you enter in the form.', 'awenly-directory' ); ?></p>
			</div>
		</aside>
		<div class="awd-contact-main">
			<h2><?php esc_html_e( 'Send us a message', 'awenly-directory' ); ?></h2>
			<p class="awd-muted"><?php esc_html_e( 'Fields marked * are required.', 'awenly-directory' ); ?></p>
			<?php echo awd_contact_form_html(); // phpcs:ignore ?>
		</div>
	</section>
</div>
<?php
get_footer();
