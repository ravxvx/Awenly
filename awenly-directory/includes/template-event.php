<?php
/**
 * Single event page (The Events Calendar) in the Awenly design.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$id      = get_the_ID();
	$start   = tribe_get_start_date( $id, false, 'U' );
	$end     = tribe_get_end_date( $id, false, 'U' );
	$allday  = function_exists( 'tribe_event_is_all_day' ) && tribe_event_is_all_day( $id );
	$past    = function_exists( 'tribe_is_past_event' ) && tribe_is_past_event( $id );
	$same    = wp_date( 'Ymd', $start ) === wp_date( 'Ymd', $end );
	$when    = $same ? wp_date( 'l, j F Y', $start ) : wp_date( 'j M', $start ) . ' – ' . wp_date( 'j M Y', $end );
	$time    = $allday ? __( 'All day', 'awenly-directory' ) : wp_date( 'H:i', $start ) . ( $end > $start ? ' – ' . wp_date( 'H:i', $end ) : '' ) . ' ' . wp_date( 'T', $start );
	$venue   = function_exists( 'tribe_get_venue' ) ? trim( wp_strip_all_tags( (string) tribe_get_venue( $id ) ) ) : '';
	$address = function_exists( 'tribe_get_full_address' ) ? trim( wp_strip_all_tags( (string) tribe_get_full_address( $id ) ) ) : '';
	$where   = get_post_meta( $id, '_awenly_event_location', true );
	if ( ! $where ) {
		$where = trim( $venue . ( $address && false === stripos( $venue, $address ) ? ', ' . $address : '' ), ', ' );
	}
	$online  = (bool) preg_match( '/\b(online|zoom|virtual|webinar|teams|google meet)\b/i', $where . ' ' . $venue );
	$cost    = function_exists( 'tribe_get_cost' ) ? trim( (string) tribe_get_cost( $id, true ) ) : '';
	$link    = function_exists( 'tribe_get_event_website_url' ) ? tribe_get_event_website_url( $id ) : '';
	$gcal    = function_exists( 'tribe_get_gcal_link' ) ? tribe_get_gcal_link( $id ) : '';
	$ical    = function_exists( 'tribe_get_single_ical_link' ) ? tribe_get_single_ical_link() : '';
	$cats    = get_the_terms( $id, 'tribe_events_cat' );
	$orgs    = function_exists( 'tribe_get_organizer_ids' ) ? tribe_get_organizer_ids( $id ) : array();
	$listing = (int) get_post_meta( $id, '_awd_listing', true );
	$author  = (int) get_post_field( 'post_author', $id );
	$host    = get_post_meta( $id, '_awd_member_event', true ) && $author ? get_userdata( $author ) : null;
	$img     = get_the_post_thumbnail_url( $id, 'large' );
	$share   = rawurlencode( get_permalink() );
	$more    = tribe_get_events( array( 'posts_per_page' => 3, 'start_date' => 'now', 'post__not_in' => array( $id ) ) );
	?>
	<div class="awd-directory awd-evpage">
		<section class="awd-event__hero"<?php echo $img ? ' style="--awd-ev-img:url(' . esc_url( $img ) . ')"' : ''; ?>>
			<div class="awd-container awd-event__head">
				<a class="awd-event__back" href="<?php echo esc_url( awd_events_list_url() ); ?>">← <?php esc_html_e( 'All events', 'awenly-directory' ); ?></a>
				<?php if ( $past ) : ?><div class="awd-event__past"><?php esc_html_e( 'This event has already taken place.', 'awenly-directory' ); ?> <a href="<?php echo esc_url( awd_events_list_url() ); ?>"><?php esc_html_e( 'See upcoming events', 'awenly-directory' ); ?> →</a></div><?php endif; ?>
				<div class="awd-event__intro">
					<span class="awd-ev-date awd-ev-date--lg"><small><?php echo esc_html( wp_date( 'M', $start ) ); ?></small><b><?php echo esc_html( wp_date( 'd', $start ) ); ?></b><small><?php echo esc_html( wp_date( 'D', $start ) ); ?></small></span>
					<div>
						<div class="awd-event__chips">
							<span class="awd-chip"><?php echo awd_icon( $online ? 'video' : 'pin' ); // phpcs:ignore ?><?php echo esc_html( $online ? __( 'Online', 'awenly-directory' ) : ( $where ? $where : __( 'Location to be confirmed', 'awenly-directory' ) ) ); ?></span>
							<?php if ( $cost ) : ?><span class="awd-chip"><?php echo awd_icon( 'tag' ); // phpcs:ignore ?><?php echo esc_html( $cost ); ?></span><?php endif; ?>
							<?php if ( $cats && ! is_wp_error( $cats ) ) : foreach ( $cats as $c ) : ?><a class="awd-chip awd-chip--soft" href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a><?php endforeach; endif; ?>
						</div>
						<h1><?php the_title(); ?></h1>
						<p class="awd-event__when"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?> <?php echo esc_html( $when ); ?> · <?php echo esc_html( $time ); ?></p>
						<div class="awd-ev-actions">
							<?php if ( $link && ! $past ) : ?><a class="awd-btn" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Book / more info', 'awenly-directory' ); ?></a><?php endif; ?>
							<?php if ( ! $past && ( $gcal || $ical ) ) : ?>
								<details class="awd-event__cal"><summary class="awd-btn awd-btn--ghost"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?> <?php esc_html_e( 'Add to calendar', 'awenly-directory' ); ?></summary>
									<div><?php if ( $gcal ) : ?><a href="<?php echo esc_url( $gcal ); ?>" target="_blank" rel="noopener">Google Calendar</a><?php endif; ?><?php if ( $ical ) : ?><a href="<?php echo esc_url( $ical ); ?>">Apple / Outlook (.ics)</a><?php endif; ?></div>
								</details>
							<?php endif; ?>
							<details class="awd-event__cal"><summary class="awd-btn awd-btn--ghost"><?php esc_html_e( 'Share', 'awenly-directory' ); ?></summary>
								<div>
									<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $share ); ?>" target="_blank" rel="noopener">Facebook</a>
									<a href="https://wa.me/?text=<?php echo esc_attr( rawurlencode( get_the_title() . ' ' ) . $share ); ?>" target="_blank" rel="noopener">WhatsApp</a>
									<a href="mailto:?subject=<?php echo esc_attr( rawurlencode( get_the_title() ) ); ?>&amp;body=<?php echo esc_attr( $share ); ?>">E-mail</a>
								</div>
							</details>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="awd-container awd-event__grid">
			<article class="awd-event__body">
				<?php if ( $img ) : ?><figure class="awd-event__cover"><?php the_post_thumbnail( 'large' ); ?></figure><?php endif; ?>
				<h2><?php esc_html_e( 'About this event', 'awenly-directory' ); ?></h2>
				<div class="awd-event__content"><?php the_content(); ?></div>
			</article>
			<aside class="awd-event__side">
				<div class="awd-event__box">
					<div class="awd-event__row"><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Date and time', 'awenly-directory' ); ?></strong><span><?php echo esc_html( $when ); ?><br><?php echo esc_html( $time ); ?></span></div></div>
					<div class="awd-event__row"><?php echo awd_icon( $online ? 'video' : 'pin' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Location', 'awenly-directory' ); ?></strong><span><?php echo esc_html( $where ? $where : ( $online ? __( 'Online', 'awenly-directory' ) : __( 'To be confirmed', 'awenly-directory' ) ) ); ?></span>
						<?php if ( ! $online && $address ) : ?><a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open in Maps', 'awenly-directory' ); ?></a><?php endif; ?></div></div>
					<div class="awd-event__row"><?php echo awd_icon( 'tag' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Price', 'awenly-directory' ); ?></strong><span><?php echo esc_html( $cost ? $cost : __( 'See booking link', 'awenly-directory' ) ); ?></span></div></div>
					<?php if ( $link && ! $past ) : ?><a class="awd-btn awd-event__book" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Book / more info', 'awenly-directory' ); ?></a><?php endif; ?>
				</div>
				<?php
				$hosts = array();
				if ( $listing && 'publish' === get_post_status( $listing ) ) {
					$hosts[] = array( get_the_post_thumbnail_url( $listing, 'thumbnail' ), get_the_title( $listing ), __( 'Practitioner', 'awenly-directory' ), get_permalink( $listing ), __( 'View listing', 'awenly-directory' ) );
				} elseif ( $host ) {
					$hosts[] = array( get_avatar_url( $host->ID, array( 'size' => 96 ) ), $host->display_name, __( 'Community member', 'awenly-directory' ), function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $host->ID ) : '', __( 'View profile', 'awenly-directory' ) );
				}
				foreach ( (array) $orgs as $oid ) {
					$hosts[] = array( get_the_post_thumbnail_url( $oid, 'thumbnail' ), tribe_get_organizer( $oid ), __( 'Organiser', 'awenly-directory' ), tribe_get_organizer_website_url( $oid ), __( 'Website', 'awenly-directory' ) );
				}
				if ( $hosts ) : ?>
					<div class="awd-event__box">
						<h3><?php esc_html_e( 'Hosted by', 'awenly-directory' ); ?></h3>
						<?php foreach ( $hosts as $h ) : ?>
							<div class="awd-event__host">
								<span class="awd-event__avatar"><?php if ( $h[0] ) : ?><img src="<?php echo esc_url( $h[0] ); ?>" alt="" loading="lazy"><?php else : ?><?php echo esc_html( mb_substr( wp_strip_all_tags( $h[1] ), 0, 1 ) ); ?><?php endif; ?></span>
								<div><strong><?php echo esc_html( wp_strip_all_tags( $h[1] ) ); ?></strong><small><?php echo esc_html( $h[2] ); ?></small><?php if ( $h[3] ) : ?><a href="<?php echo esc_url( $h[3] ); ?>"<?php echo 0 === strpos( $h[3], home_url() ) ? '' : ' target="_blank" rel="noopener"'; ?>><?php echo esc_html( $h[4] ); ?> →</a><?php endif; ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="awd-event__box awd-event__box--cta">
					<strong><?php esc_html_e( 'Running your own event?', 'awenly-directory' ); ?></strong>
					<p><?php esc_html_e( 'Add workshops, circles and online sessions to the calendar for free.', 'awenly-directory' ); ?></p>
					<a class="awd-btn awd-btn--sm" href="<?php echo esc_url( awd_add_event_url() ); ?>"><?php esc_html_e( '+ Add your event', 'awenly-directory' ); ?></a>
				</div>
			</aside>
		</section>

		<?php if ( $more ) : ?>
			<section class="awd-container awd-event__more">
				<h2><?php esc_html_e( 'More upcoming events', 'awenly-directory' ); ?></h2>
				<div class="awd-event__cards">
					<?php foreach ( $more as $e ) :
						$s = tribe_get_start_date( $e->ID, false, 'U' ); ?>
						<a class="awd-event__card" href="<?php echo esc_url( get_permalink( $e ) ); ?>">
							<span class="awd-event__card-img"<?php echo has_post_thumbnail( $e ) ? ' style="background-image:url(' . esc_url( get_the_post_thumbnail_url( $e, 'medium_large' ) ) . ')"' : ''; ?>></span>
							<span class="awd-event__card-body"><small><?php echo esc_html( wp_date( 'D, j M · H:i', $s ) ); ?></small><strong><?php echo esc_html( get_the_title( $e ) ); ?></strong></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
