<?php
/**
 * Template for /add-events/, /my-events/ and /event-directory/ (replaces their old Elementor layouts).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="awd-directory awd-events-page">
	<section class="awd-hero awd-contact-hero" style="padding-bottom:40px">
		<div class="awd-container">
			<span class="awd-kicker"><?php esc_html_e( 'Events', 'awenly-directory' ); ?></span>
			<h1><?php echo esc_html( get_the_title() ); ?></h1>
		</div>
	</section>
	<section class="awd-container" style="padding-bottom:60px">
		<?php echo do_shortcode( awd_event_page_shortcode() ); // phpcs:ignore ?>
	</section>
</div>
<?php
get_footer();
