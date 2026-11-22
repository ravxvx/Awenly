<?php
/**
 * Site contact form ([awenly_contact_form]) — also fills the empty Elementor "Form" widget
 * (that widget needs Elementor Pro, which is not installed).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function awd_contact_form_html() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done   = true;
	$u      = wp_get_current_user();
	$state  = sanitize_key( $_GET['awd_c'] ?? '' );
	$states = array(
		'sent'    => array( 'ok', __( 'Thank you! Your message has been sent. We will get back to you soon.', 'awenly-directory' ) ),
		'invalid' => array( 'err', __( 'Please add your name, a valid email and a short message, and tick the consent box.', 'awenly-directory' ) ),
		'limit'   => array( 'err', __( 'Too many messages from your connection. Please try again later.', 'awenly-directory' ) ),
		'expired' => array( 'err', __( 'The form expired. Please try again.', 'awenly-directory' ) ),
	);
	$topics = array( __( 'General question', 'awenly-directory' ), __( 'Membership & account', 'awenly-directory' ), __( 'Practitioner listing', 'awenly-directory' ), __( 'Events', 'awenly-directory' ), __( 'Partnership & press', 'awenly-directory' ), __( 'Technical problem', 'awenly-directory' ) );
	ob_start();
	?>
	<div class="awd-contact awd-contact--site" id="awenly-contact">
		<?php if ( isset( $states[ $state ] ) ) : ?><div class="awd-notice awd-notice--<?php echo esc_attr( $states[ $state ][0] ); ?>"><?php echo esc_html( $states[ $state ][1] ); ?></div><?php endif; ?>
		<?php if ( 'sent' !== $state ) : ?>
		<form method="post" class="awd-contact__form" action="<?php echo esc_url( remove_query_arg( 'awd_c' ) ); ?>#awenly-contact">
			<?php wp_nonce_field( 'awd_contact', 'awd_cnonce' ); ?>
			<input type="hidden" name="awd_contact" value="1"><input type="hidden" name="awd_t" value="<?php echo (int) time(); ?>">
			<label class="awd-hp" aria-hidden="true">Website<input type="text" name="awd_website_hp" tabindex="-1" autocomplete="off"></label>
			<div class="awd-grid">
				<label class="awd-field"><span><?php esc_html_e( 'Your name *', 'awenly-directory' ); ?></span><input type="text" name="awd_c_name" required maxlength="80" value="<?php echo esc_attr( $u->exists() ? $u->display_name : '' ); ?>"></label>
				<label class="awd-field"><span><?php esc_html_e( 'Email *', 'awenly-directory' ); ?></span><input type="email" name="awd_c_email" required value="<?php echo esc_attr( $u->exists() ? $u->user_email : '' ); ?>"></label>
				<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Topic', 'awenly-directory' ); ?></span><select name="awd_c_topic"><?php foreach ( $topics as $t ) : ?><option><?php echo esc_html( $t ); ?></option><?php endforeach; ?></select></label>
				<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Message *', 'awenly-directory' ); ?></span><textarea name="awd_c_text" rows="6" required minlength="10" maxlength="5000"></textarea></label>
			</div>
			<label class="awd-check"><input type="checkbox" name="awd_c_consent" value="1" required> <?php esc_html_e( 'I agree that Awenly Circle stores my message and contact details to reply to me.', 'awenly-directory' ); ?></label>
			<button type="submit" class="awd-btn"><?php esc_html_e( 'Send message', 'awenly-directory' ); ?></button>
		</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'awenly_contact_form', 'awd_contact_form_html' );

// Fill the Elementor Pro "Form" widget when Elementor Pro is missing (it renders empty).
add_filter( 'elementor/widget/render_content', function ( $content, $widget ) {
	if ( is_object( $widget ) && method_exists( $widget, 'get_name' ) && 'form' === $widget->get_name() && '' === trim( wp_strip_all_tags( (string) $content ) ) ) {
		return awd_contact_form_html();
	}
	return $content;
}, 10, 2 );

// Fallback for the Contact page if the widget filter did not run.
add_filter( 'the_content', function ( $content ) {
	if ( is_page( array( 'contact-us', 'contact' ) ) && in_the_loop() && is_main_query() && false === strpos( $content, 'awd-contact--site' ) ) {
		$content .= awd_contact_form_html();
	}
	return $content;
}, 99 );

add_action( 'template_redirect', function () {
	if ( empty( $_POST['awd_contact'] ) ) {
		return;
	}
	$back = remove_query_arg( 'awd_c', wp_get_referer() ? wp_get_referer() : home_url( '/contact-us/' ) );
	$go   = function ( $flag ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'awd_c', $flag, $back ) . '#awenly-contact' );
		exit;
	};
	if ( ! isset( $_POST['awd_cnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_cnonce'] ) ), 'awd_contact' ) ) {
		$go( 'expired' );
	}
	if ( ! empty( $_POST['awd_website_hp'] ) || ( time() - (int) ( $_POST['awd_t'] ?? 0 ) ) < 3 ) {
		$go( 'sent' );
	}
	$ip_key = 'awd_c_' . md5( awd_client_ip() );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		$go( 'limit' );
	}
	$name  = mb_substr( sanitize_text_field( wp_unslash( $_POST['awd_c_name'] ?? '' ) ), 0, 80 );
	$email = sanitize_email( wp_unslash( $_POST['awd_c_email'] ?? '' ) );
	$topic = mb_substr( sanitize_text_field( wp_unslash( $_POST['awd_c_topic'] ?? '' ) ), 0, 60 );
	$text  = mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST['awd_c_text'] ?? '' ) ) ), 0, 5000 );
	if ( ! $name || ! is_email( $email ) || mb_strlen( $text ) < 10 || empty( $_POST['awd_c_consent'] ) ) {
		$go( 'invalid' );
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	$id     = wp_insert_post( array(
		'post_type'    => 'awd_enquiry',
		'post_status'  => 'private',
		'post_title'   => sprintf( 'Contact: %s — %s', $name, $topic ),
		'post_content' => $text,
		'post_author'  => $admins ? (int) $admins[0] : 0,
	) );
	if ( $id ) {
		update_post_meta( $id, '_awd_q_name', $name );
		update_post_meta( $id, '_awd_q_email', $email );
	}
	wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[%s] %s: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $topic, $name ),
		sprintf( "New message from the contact form.\n\nName: %s\nEmail: %s\nTopic: %s\n\n%s\n\nJust reply to this email to answer.\n", $name, $email, $topic, $text ),
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);
	$go( 'sent' );
}, 1 );

function awd_is_contact_page() {
	return is_page( array( 'contact-us', 'contact' ) );
}

add_filter( 'template_include', function ( $template ) {
	return awd_is_contact_page() ? AWD_DIR . 'includes/template-contact.php' : $template;
}, 100 );

add_filter( 'body_class', function ( $c ) {
	if ( awd_is_contact_page() ) {
		$c[] = 'awd-page';
	}
	return $c;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( awd_is_contact_page() ) {
		wp_enqueue_style( 'awenly-directory', AWD_URI . 'assets/directory.css', array(), AWD_VERSION );
	}
}, 10002 );
