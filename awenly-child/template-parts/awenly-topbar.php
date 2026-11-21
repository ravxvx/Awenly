<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$networks = array( 'facebook', 'instagram', 'youtube', 'linkedin' );
?>
<div class="awenly-topbar" role="region" aria-label="<?php esc_attr_e( 'Site announcements', 'awenly-child' ); ?>">
	<div class="awenly-topbar__inner">
		<div class="awenly-topbar__social">
			<?php foreach ( $networks as $n ) :
				$url = awenly_opt( 'social_' . $n );
				if ( ! $url ) {
					continue;
				} ?>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $n ) ); ?>"><?php echo awenly_social_icon( $n ); // phpcs:ignore ?></a>
			<?php endforeach; ?>
		</div>
		<p class="awenly-topbar__text"><?php echo esc_html( awenly_opt( 'topbar_text' ) ); ?></p>
		<div class="awenly-topbar__actions">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="awenly-topbar__login" href="<?php echo esc_url( function_exists( 'bp_loggedin_user_domain' ) ? bp_loggedin_user_domain() : admin_url( 'profile.php' ) ); ?>"><?php esc_html_e( 'My profile', 'awenly-child' ); ?></a>
				<a class="awenly-topbar__login" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log out', 'awenly-child' ); ?></a>
			<?php else : ?>
				<a class="awenly-topbar__login" href="<?php echo esc_url( awenly_opt( 'login_url' ) ); ?>"><?php esc_html_e( 'Login', 'awenly-child' ); ?></a>
				<a class="awenly-topbar__join" href="<?php echo esc_url( awenly_opt( 'join_url' ) ); ?>"><?php echo awenly_icon( 'user' ); // phpcs:ignore ?><?php esc_html_e( 'Join the Circle', 'awenly-child' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
