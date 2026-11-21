<?php
/**
 * Single practitioner listing template.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$post    = get_post();
	$terms   = wp_get_object_terms( $post->ID, AWD_TAX );
	$modes   = awd_fields()['mode']['options'];
	$mode    = awd_meta( $post->ID, 'mode' );
	$booking = awd_meta( $post->ID, 'booking_url' );
	$website = awd_meta( $post->ID, 'website' );
	$msg     = awd_message_url( $post );
	$profile = awd_owner_profile_url( $post );
	$details = array(
		array( 'pin', __( 'Location', 'awenly-directory' ), awd_meta( $post->ID, 'location' ) ),
		array( 'video', __( 'Sessions', 'awenly-directory' ), $mode ? ( $modes[ $mode ] ?? $mode ) : '' ),
		array( 'tag', __( 'Price from', 'awenly-directory' ), awd_meta( $post->ID, 'fee' ) ),
		array( 'lang', __( 'Languages', 'awenly-directory' ), awd_meta( $post->ID, 'languages' ) ),
		array( 'cert', __( 'Training', 'awenly-directory' ), awd_meta( $post->ID, 'qualifications' ) ),
	);
	?>
	<div class="awd-single">
		<section class="awd-single__hero">
			<div class="awd-container awd-single__grid">
				<div class="awd-single__photo"><?php echo awd_photo( $post, 'large' ); // phpcs:ignore ?></div>
				<div class="awd-single__intro">
					<a class="awd-back" href="<?php echo esc_url( awd_directory_url() ); ?>">← <?php esc_html_e( 'All practitioners', 'awenly-directory' ); ?></a>
					<span class="awd-kicker"><?php esc_html_e( 'Awenly Circle practitioner', 'awenly-directory' ); ?></span>
					<h1><?php the_title(); ?></h1>
					<?php if ( $post->post_excerpt ) : ?><p class="awd-single__tag"><?php echo esc_html( $post->post_excerpt ); ?></p><?php endif; ?>
					<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
						<div class="awd-chips"><?php foreach ( $terms as $t ) : ?><a href="<?php echo esc_url( add_query_arg( 'specialty', $t->slug, awd_directory_url() ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?></div>
					<?php endif; ?>
					<div class="awd-actions">
						<?php if ( $booking ) : ?><a class="awd-btn" href="<?php echo esc_url( $booking ); ?>" target="_blank" rel="noopener"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><?php esc_html_e( 'Book a session', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $msg ) : ?><a class="awd-btn <?php echo $booking ? 'awd-btn--ghost' : ''; ?>" href="<?php echo esc_url( $msg ); ?>"><?php echo awd_icon( 'msg' ); // phpcs:ignore ?><?php esc_html_e( 'Send a message', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $profile ) : ?><a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( $profile ); ?>"><?php echo awd_icon( 'user' ); // phpcs:ignore ?><?php esc_html_e( 'Community profile', 'awenly-directory' ); ?></a><?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="awd-container awd-single__body">
			<div class="awd-single__about entry-content">
				<h2><?php esc_html_e( 'About', 'awenly-directory' ); ?></h2>
				<?php the_content(); ?>
			</div>
			<aside class="awd-single__details">
				<h2><?php esc_html_e( 'Details', 'awenly-directory' ); ?></h2>
				<ul>
					<?php foreach ( $details as $d ) :
						if ( '' === $d[2] ) {
							continue;
						} ?>
						<li><?php echo awd_icon( $d[0] ); // phpcs:ignore ?><span><small><?php echo esc_html( $d[1] ); ?></small><?php echo esc_html( $d[2] ); ?></span></li>
					<?php endforeach; ?>
					<?php if ( $website ) : ?>
						<li><?php echo awd_icon( 'globe' ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Website', 'awenly-directory' ); ?></small><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $website ) ) ); ?></a></span></li>
					<?php endif; ?>
				</ul>
			</aside>
		</section>
	</div>
	<?php
endwhile;
get_footer();
