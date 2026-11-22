<?php
/**
 * Single listing template (practitioner, centre, retreat, venue, shop, course).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$post     = get_post();
	$type     = awd_type( $post );
	$types    = awd_types();
	$terms    = wp_get_object_terms( $post->ID, AWD_TAX );
	$modes    = awd_fields()['mode']['options'];
	$mode     = awd_meta( $post->ID, 'mode' );
	$booking  = awd_meta( $post->ID, 'booking_url' );
	$website  = awd_meta( $post->ID, 'website' );
	$msg      = awd_message_url( $post );
	$profile  = awd_owner_profile_url( $post );
	$uid      = (int) $post->post_author;
	$is_owner = get_current_user_id() === $uid;
	$is_person = 'practitioner' === $type;
	$details  = array(
		array( 'cal', __( 'Dates', 'awenly-directory' ), awd_date_range( $post->ID ) ),
		array( 'pin', __( 'Location', 'awenly-directory' ), awd_meta( $post->ID, 'location' ) ),
		array( 'video', __( 'Format', 'awenly-directory' ), $mode ? ( $modes[ $mode ] ?? $mode ) : '' ),
		array( 'tag', __( 'Price from', 'awenly-directory' ), awd_meta( $post->ID, 'fee' ) ),
		array( 'users', __( 'Capacity', 'awenly-directory' ), awd_meta( $post->ID, 'capacity' ) ),
		array( 'clock', __( 'Opening hours', 'awenly-directory' ), awd_meta( $post->ID, 'hours' ) ),
		array( 'lang', __( 'Languages', 'awenly-directory' ), awd_meta( $post->ID, 'languages' ) ),
		array( 'cert', __( 'Training', 'awenly-directory' ), awd_meta( $post->ID, 'qualifications' ) ),
	);
	$cover    = awd_cover_url( $uid );
	$socials  = awd_social_links( $uid );
	if ( $website ) {
		$socials = array( 'website' => $website ) + $socials;
	}
	$stats    = $is_person ? awd_bp_stats( $uid ) : array();
	$bpfields = $is_person ? awd_bp_fields( $uid ) : array();
	$services = array_filter( (array) get_post_meta( $post->ID, '_awd_services', true ), 'is_array' );
	$gallery  = array_filter( array_map( 'absint', (array) get_post_meta( $post->ID, '_awd_gallery', true ) ) );
	$video    = awd_meta( $post->ID, 'video' );
	$embed    = $video ? wp_oembed_get( $video, array( 'width' => 960 ) ) : '';
	$kickers  = array(
		'practitioner' => __( 'Awenly Circle practitioner', 'awenly-directory' ),
		'centre'       => __( 'Centre & studio', 'awenly-directory' ),
		'retreat'      => __( 'Retreat', 'awenly-directory' ),
		'venue'        => __( 'Venue to rent', 'awenly-directory' ),
		'shop'         => __( 'Shop & products', 'awenly-directory' ),
		'course'       => __( 'Course', 'awenly-directory' ),
	);
	$book_lbl = array(
		'practitioner' => __( 'Book a session', 'awenly-directory' ),
		'centre'       => __( 'Book now', 'awenly-directory' ),
		'retreat'      => __( 'Book your place', 'awenly-directory' ),
		'venue'        => __( 'Check availability', 'awenly-directory' ),
		'shop'         => __( 'Visit the shop', 'awenly-directory' ),
		'course'       => __( 'Enrol now', 'awenly-directory' ),
	);
	$primary = $booking ? $booking : ( 'shop' === $type ? $website : '' );
	?>
	<div class="awd-single awd-single--<?php echo esc_attr( $type ); ?>">
		<section class="awd-single__hero">
			<div class="awd-single__cover<?php echo $cover ? ' has-image' : ''; ?>"<?php echo $cover ? ' style="background-image:url(' . esc_url( $cover ) . ')"' : ''; ?>>
				<div class="awd-container"><a class="awd-back" href="<?php echo esc_url( awd_directory_url() ); ?>">← <?php esc_html_e( 'All practitioners', 'awenly-directory' ); ?></a></div>
			</div>
			<div class="awd-container">
				<div class="awd-single__card">
					<div class="awd-single__photo"><?php echo awd_photo( $post, 'large' ); // phpcs:ignore ?></div>
					<div class="awd-single__intro">
						<span class="awd-kicker"><?php echo esc_html( $kickers[ $type ] ); ?><?php echo awd_is_featured( $post->ID ) ? ' · <b class="awd-featured-tag">' . esc_html__( 'Featured', 'awenly-directory' ) . '</b>' : ''; // phpcs:ignore ?></span>
						<h1><?php the_title(); ?></h1>
						<?php if ( $post->post_excerpt ) : ?><p class="awd-single__tag"><?php echo esc_html( $post->post_excerpt ); ?></p><?php endif; ?>
						<div class="awd-single__badges">
							<?php echo awd_is_verified( $post->ID ) ? awd_verified_badge() : ''; // phpcs:ignore ?>
							<?php $rt = awd_rating( $post->ID ); ?><a href="#reviews" class="awd-single__rating"><?php echo awd_stars( $rt['avg'], $rt['count'] ); // phpcs:ignore ?></a>
							<?php if ( awd_date_range( $post->ID ) ) : ?><span class="awd-single__loc"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><?php echo esc_html( awd_date_range( $post->ID ) ); ?></span><?php endif; ?>
							<?php if ( awd_meta( $post->ID, 'location' ) ) : ?><span class="awd-single__loc"><?php echo awd_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( awd_meta( $post->ID, 'location' ) ); ?></span><?php endif; ?>
						</div>
						<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
							<div class="awd-chips"><?php foreach ( $terms as $t ) : ?><a href="<?php echo esc_url( add_query_arg( 'specialty', $t->slug, awd_directory_url() ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?></div>
						<?php endif; ?>
						<?php if ( $socials ) : ?>
							<div class="awd-socials">
								<?php foreach ( $socials as $net => $url ) : ?>
									<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow" <?php echo 'website' === $net ? 'data-awd-track="web"' : ''; ?> title="<?php echo esc_attr( 0 === strpos( $net, 'link-' ) ? __( 'Link', 'awenly-directory' ) : ucfirst( $net ) ); ?>" aria-label="<?php echo esc_attr( 0 === strpos( $net, 'link-' ) ? __( 'Link', 'awenly-directory' ) : ucfirst( $net ) ); ?>"><?php echo awd_social_icon( $net ); // phpcs:ignore ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php awd_render_share( $post ); ?>
					</div>
					<div class="awd-single__cta">
						<?php if ( $primary ) : ?><a class="awd-btn" href="<?php echo esc_url( $primary ); ?>" target="_blank" rel="noopener" data-awd-track="book"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><?php echo esc_html( $book_lbl[ $type ] ); ?></a><?php endif; ?>
						<?php if ( ! $is_owner ) : ?><a class="awd-btn <?php echo $primary ? 'awd-btn--ghost' : ''; ?>" href="#contact"><?php echo awd_icon( 'msg' ); // phpcs:ignore ?><?php esc_html_e( 'Send an enquiry', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $msg ) : ?><a class="awd-btn awd-btn--ghost" href="<?php echo esc_url( $msg ); ?>" data-awd-track="msg"><?php echo awd_icon( 'msg' ); // phpcs:ignore ?><?php esc_html_e( 'Private message', 'awenly-directory' ); ?></a><?php endif; ?>
						<?php if ( $is_owner ) : ?><a class="awd-btn" href="<?php echo esc_url( add_query_arg( 'edit', $post->ID, awd_practice_url() ) ); ?>"><?php echo awd_icon( 'user' ); // phpcs:ignore ?><?php esc_html_e( 'Edit listing', 'awenly-directory' ); ?></a><?php endif; ?>
						<div class="awd-single__mini">
							<?php echo awd_save_button( $post ); // phpcs:ignore ?>
							<?php if ( $profile ) : ?><a class="awd-mini-link" href="<?php echo esc_url( $profile ); ?>"><?php echo awd_icon( 'user' ); // phpcs:ignore ?><?php esc_html_e( 'Community profile', 'awenly-directory' ); ?></a><?php endif; ?>
						</div>
						<?php awd_render_social_buttons( $uid ); ?>
					</div>
					<?php if ( $stats ) : ?>
						<ul class="awd-single__stats">
							<?php foreach ( $stats as $st ) : ?><li><b><?php echo esc_html( $st[1] ); ?></b><small><?php echo esc_html( $st[0] ); ?></small></li><?php endforeach; ?>
						</ul>
					<?php elseif ( ! $is_person ) : ?>
						<div class="awd-single__by"><?php echo get_avatar( $uid, 32 ); ?> <?php printf( esc_html__( 'Listed by %s', 'awenly-directory' ), '<a href="' . esc_url( $profile ) . '">' . esc_html( get_the_author_meta( 'display_name', $uid ) ) . '</a>' ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<?php if ( $embed ) : ?>
			<section class="awd-container awd-single__video"><div class="awd-video"><?php echo $embed; // phpcs:ignore ?></div></section>
		<?php endif; ?>

		<section class="awd-container awd-single__body">
			<div class="awd-single__main">
				<div class="awd-single__about entry-content">
					<h2><?php esc_html_e( 'About', 'awenly-directory' ); ?></h2>
					<?php if ( trim( wp_strip_all_tags( $post->post_content ) ) ) { the_content(); } else { echo '<p class="awd-muted">' . esc_html__( 'No introduction yet.', 'awenly-directory' ) . '</p>'; } ?>
					<?php if ( $bpfields ) : ?>
						<h3 class="awd-single__sub"><?php esc_html_e( 'From the community profile', 'awenly-directory' ); ?></h3>
						<dl class="awd-single__fields">
							<?php foreach ( $bpfields as $f ) : ?><div><dt><?php echo esc_html( trim( preg_replace( '/[^\p{L}\p{N}\s\/&\-\.,()]/u', '', $f[0] ) ) ); ?></dt><dd><?php if ( preg_match( '#^https?://\S+$#', $f[1] ) ) : ?><a href="<?php echo esc_url( $f[1] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $f[1] ) ) ); ?></a><?php else : echo esc_html( $f[1] ); endif; ?></dd></div><?php endforeach; ?>
						</dl>
					<?php endif; ?>
				</div>

				<?php if ( $services ) : ?>
					<div class="awd-single__services">
						<h2><?php echo 'shop' === $type ? esc_html__( 'Products & prices', 'awenly-directory' ) : esc_html__( 'Services & prices', 'awenly-directory' ); ?></h2>
						<ul class="awd-services">
							<?php foreach ( $services as $s ) : ?>
								<li>
									<div class="awd-services__main"><strong><?php echo esc_html( $s['name'] ); ?></strong>
										<?php if ( ! empty( $s['desc'] ) ) : ?><p><?php echo esc_html( $s['desc'] ); ?></p><?php endif; ?>
										<?php if ( ! empty( $s['duration'] ) ) : ?><small><?php echo awd_icon( 'clock' ); // phpcs:ignore ?><?php echo esc_html( $s['duration'] ); ?></small><?php endif; ?>
									</div>
									<?php if ( ! empty( $s['price'] ) ) : ?><span class="awd-services__price"><?php echo esc_html( $s['price'] ); ?></span><?php endif; ?>
									<?php $surl = ! empty( $s['url'] ) ? $s['url'] : $primary; if ( $surl ) : ?><a class="awd-btn awd-btn--sm" href="<?php echo esc_url( $surl ); ?>" target="_blank" rel="noopener" data-awd-track="service"><?php echo 'shop' === $type ? esc_html__( 'Buy', 'awenly-directory' ) : esc_html__( 'Book', 'awenly-directory' ); ?></a><?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $gallery ) : ?>
					<div class="awd-single__gallery">
						<h2><?php esc_html_e( 'Gallery', 'awenly-directory' ); ?></h2>
						<div class="awd-gallery">
							<?php foreach ( $gallery as $att ) : $full = wp_get_attachment_image_url( $att, 'large' ); if ( ! $full ) { continue; } ?>
								<a href="<?php echo esc_url( $full ); ?>" data-awd-lightbox><?php echo wp_get_attachment_image( $att, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?></a>
							<?php endforeach; ?>
						</div>
					</div>
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
						<li><?php echo awd_icon( 'globe' ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Website', 'awenly-directory' ); ?></small><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener nofollow" data-awd-track="web"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $website ) ) ); ?></a></span></li>
					<?php endif; ?>
				</ul>
				<?php awd_render_map( awd_map_points( array( $post->ID ) ), 'awd-map--single' ); ?>
			</aside>
		</section>

		<?php
		$events = awd_practitioner_events( $uid );
		if ( awd_events_active() && ( $events || $is_person || $is_owner ) ) : ?>
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

		<div class="awd-container"><?php awd_render_enquiry_form( $post ); ?></div>
		<div class="awd-container"><?php awd_render_reviews( $post ); ?></div>

		<?php $similar = awd_similar_listings( $post ); if ( $similar ) : ?>
			<section class="awd-container awd-similar">
				<h2><?php esc_html_e( 'You may also like', 'awenly-directory' ); ?></h2>
				<div class="awd-grid-cards"><?php foreach ( $similar as $sp ) { awd_render_card( $sp ); } ?></div>
			</section>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
