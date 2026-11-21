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
	<?php
	$uid     = (int) $post->post_author;
	$cover   = awd_cover_url( $uid );
	$socials = awd_social_links( $uid );
	if ( $website ) {
		$socials = array( 'website' => $website ) + $socials;
	}
	$stats    = awd_bp_stats( $uid );
	$bpfields = awd_bp_fields( $uid );
	$is_owner = get_current_user_id() === $uid;
	?>
	<div class="awd-single">
		<section class="awd-single__hero">
			<div class="awd-single__cover<?php echo $cover ? ' has-image' : ''; ?>"<?php echo $cover ? ' style="background-image:url(' . esc_url( $cover ) . ')"' : ''; ?>>
				<div class="awd-container"><a class="awd-back" href="<?php echo esc_url( awd_directory_url() ); ?>">← <?php esc_html_e( 'All practitioners', 'awenly-directory' ); ?></a></div>
			</div>
			<div class="awd-container">
				<div class="awd-single__card">
					<div class="awd-single__photo"><?php echo awd_photo( $post, 'large' ); // phpcs:ignore ?></div>
					<div class="awd-single__intro">
						<span class="awd-kicker"><?php esc_html_e( 'Awenly Circle practitioner', 'awenly-directory' ); ?></span>
						<h1><?php the_title(); ?></h1>
						<?php if ( $post->post_excerpt ) : ?><p class="awd-single__tag"><?php echo esc_html( $post->post_excerpt ); ?></p><?php endif; ?>
						<div class="awd-single__badges">
							<?php echo awd_is_verified( $post->ID ) ? awd_verified_badge() : ''; // phpcs:ignore ?>
							<?php $rt = awd_rating( $post->ID ); ?><a href="#reviews" class="awd-single__rating"><?php echo awd_stars( $rt['avg'], $rt['count'] ); // phpcs:ignore ?></a>
							<?php if ( awd_meta( $post->ID, 'location' ) ) : ?><span class="awd-single__loc"><?php echo awd_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( awd_meta( $post->ID, 'location' ) ); ?></span><?php endif; ?>
						</div>
						<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
							<div class="awd-chips"><?php foreach ( $terms as $t ) : ?><a href="<?php echo esc_url( add_query_arg( 'specialty', $t->slug, awd_directory_url() ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?></div>
						<?php endif; ?>
						<?php if ( $socials ) : ?>
							<div class="awd-socials">
								<?php foreach ( $socials as $net => $url ) : ?>
									<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr( 0 === strpos( $net, 'link-' ) ? __( 'Link', 'awenly-directory' ) : ucfirst( $net ) ); ?>" aria-label="<?php echo esc_attr( 0 === strpos( $net, 'link-' ) ? __( 'Link', 'awenly-directory' ) : ucfirst( $net ) ); ?>"><?php echo awd_social_icon( $net ); // phpcs:ignore ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
					<div class="awd-single__cta">
						<?php if ( $booking ) : ?><a class="awd-btn" href="<?php echo esc_url( $booking ); ?>" target="_blank" rel="noopener"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><?php esc_html_e( 'Book a session', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $msg ) : ?><a class="awd-btn <?php echo $booking ? 'awd-btn--ghost' : ''; ?>" href="<?php echo esc_url( $msg ); ?>"><?php echo awd_icon( 'msg' ); // phpcs:ignore ?><?php esc_html_e( 'Send a message', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $is_owner && function_exists( 'awd_practice_url' ) ) : ?><a class="awd-btn" href="<?php echo esc_url( awd_practice_url() ); ?>"><?php echo awd_icon( 'user' ); // phpcs:ignore ?><?php esc_html_e( 'Edit my listing', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $profile ) : ?><a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( $profile ); ?>"><?php echo awd_icon( 'user' ); // phpcs:ignore ?><?php esc_html_e( 'Community profile', 'awenly-directory' ); ?></a><?php endif; ?>
					</div>
					<?php if ( $stats ) : ?>
						<ul class="awd-single__stats">
							<?php foreach ( $stats as $st ) : ?><li><b><?php echo esc_html( $st[1] ); ?></b><small><?php echo esc_html( $st[0] ); ?></small></li><?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section class="awd-container awd-single__body">
			<div class="awd-single__about entry-content">
				<h2><?php esc_html_e( 'About', 'awenly-directory' ); ?></h2>
				<?php if ( trim( wp_strip_all_tags( $post->post_content ) ) ) { the_content(); } else { echo '<p class="awd-muted">' . esc_html__( 'This practitioner has not written an introduction yet.', 'awenly-directory' ) . '</p>'; } ?>
				<?php if ( $bpfields ) : ?>
					<h3 class="awd-single__sub"><?php esc_html_e( 'From the community profile', 'awenly-directory' ); ?></h3>
					<dl class="awd-single__fields">
						<?php foreach ( $bpfields as $f ) : ?><div><dt><?php echo esc_html( $f[0] ); ?></dt><dd><?php if ( preg_match( '#^https?://\S+$#', $f[1] ) ) : ?><a href="<?php echo esc_url( $f[1] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $f[1] ) ) ); ?></a><?php else : echo esc_html( $f[1] ); endif; ?></dd></div><?php endforeach; ?>
					</dl>
				<?php endif; ?>
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
				<?php awd_render_map( awd_map_points( array( $post->ID ) ), 'awd-map--single' ); ?>
			</aside>
		</section>

		<?php
		$events = awd_practitioner_events( (int) $post->post_author );
		if ( awd_events_active() ) : ?>
			<section class="awd-container awd-single__events">
				<h2><?php esc_html_e( 'Upcoming events', 'awenly-directory' ); ?></h2>
				<?php if ( ! $events ) : ?>
					<div class="awd-empty-events"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><p><?php esc_html_e( 'No upcoming events at the moment.', 'awenly-directory' ); ?></p>
						<?php if ( $is_owner ) : ?><a class="awd-btn awd-btn--sm" href="<?php echo esc_url( trailingslashit( awd_practice_url() ) . '#events' ); ?>"><?php esc_html_e( '+ Add your first event', 'awenly-directory' ); ?></a><?php else : ?><a class="awd-btn awd-btn--sm awd-btn--ghost" href="<?php echo esc_url( function_exists( 'tribe_get_events_link' ) ? tribe_get_events_link() : home_url( '/events/' ) ); ?>"><?php esc_html_e( 'Browse all events', 'awenly-directory' ); ?></a><?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="awd-event-list">
					<?php foreach ( $events as $ev ) : ?>
						<a class="awd-event" href="<?php echo esc_url( get_permalink( $ev ) ); ?>">
							<span class="awd-event__date"><b><?php echo esc_html( tribe_get_start_date( $ev->ID, false, 'd' ) ); ?></b><small><?php echo esc_html( tribe_get_start_date( $ev->ID, false, 'M' ) ); ?></small></span>
							<span><strong><?php echo esc_html( get_the_title( $ev ) ); ?></strong><small><?php echo esc_html( tribe_get_start_date( $ev->ID, false, 'D, H:i' ) ); ?><?php $w = get_post_meta( $ev->ID, '_awenly_event_location', true ); echo $w ? ' · ' . esc_html( $w ) : ''; ?></small></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="awd-container"><?php awd_render_reviews( $post ); ?></div>
	</div>
	<?php
endwhile;
get_footer();
