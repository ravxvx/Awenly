<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$join_url = awenly_opt( 'join_url' );
$dir_url  = awenly_opt( 'practitioners_url' );
?>
<footer class="awenly-footer">
	<div class="awenly-wrap awenly-footer__grid">
		<div class="awenly-footer__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="awenly-footer__logo">
				<?php echo awenly_logo_mark( 46 ); // phpcs:ignore ?>
				<span><b>Awenly</b> Circle<small>Community · Learning · Healing</small></span>
			</a>
			<p><?php echo esc_html( awenly_opt( 'topbar_text' ) ); ?></p>
			<div class="awenly-footer__social">
				<?php foreach ( array( 'facebook', 'instagram', 'youtube', 'linkedin' ) as $nw ) :
					$u = awenly_opt( 'social_' . $nw );
					if ( $u ) : ?>
						<a href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $nw ) ); ?>"><?php echo awenly_social_icon( $nw ); // phpcs:ignore ?></a>
					<?php endif;
				endforeach; ?>
			</div>
		</div>
		<nav class="awenly-footer__col" aria-label="<?php esc_attr_e( 'Explore', 'awenly-child' ); ?>">
			<h3><?php esc_html_e( 'Explore', 'awenly-child' ); ?></h3>
			<a href="<?php echo esc_url( awenly_opt( 'community_url' ) ); ?>"><?php esc_html_e( 'Community', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( $dir_url ); ?>"><?php esc_html_e( 'Practitioners', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( awenly_opt( 'events_url' ) ); ?>"><?php esc_html_e( 'Events', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( awenly_opt( 'courses_url' ) ); ?>"><?php esc_html_e( 'Learn', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'awenly-child' ); ?></a>
		</nav>
		<nav class="awenly-footer__col" aria-label="<?php esc_attr_e( 'Circle', 'awenly-child' ); ?>">
			<h3><?php esc_html_e( 'Circle', 'awenly-child' ); ?></h3>
			<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>"><?php esc_html_e( 'Contact', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/groups/' ) ); ?>"><?php esc_html_e( 'Groups', 'awenly-child' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>"><?php esc_html_e( 'Terms', 'awenly-child' ); ?></a>
		</nav>
		<div class="awenly-footer__col awenly-footer__cta">
			<h3><?php esc_html_e( 'Are you a practitioner?', 'awenly-child' ); ?></h3>
			<p><?php esc_html_e( 'Share your work with people looking for exactly what you offer.', 'awenly-child' ); ?></p>
			<a class="aw3-btn aw3-btn--lime" href="<?php echo esc_url( function_exists( 'awd_practice_url' ) ? awd_practice_url() : $join_url ); ?>"><?php esc_html_e( 'List your practice', 'awenly-child' ); ?></a>
		</div>
	</div>
	<div class="awenly-wrap awenly-footer__bottom">
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Awenly Circle</span>
		<span><?php esc_html_e( 'Connect • Learn • Heal • Grow', 'awenly-child' ); ?></span>
		<span class="awenly-footer__credit"><?php esc_html_e( 'Website by', 'awenly-child' ); ?> <a href="https://smartsite.ie/" target="_blank" rel="noopener">SmartSite.ie</a></span>
	</div>
</footer>
