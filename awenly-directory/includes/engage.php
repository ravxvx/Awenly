<?php
/**
 * Engagement: enquiries, saved listings, similar listings, sharing, click tracking,
 * Follow/Connect, featured listings, structured data, admin stats, event activity.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ===================== Enquiries ===================== */

add_action( 'init', function () {
	register_post_type( 'awd_enquiry', array(
		'labels'       => array( 'name' => __( 'Enquiries', 'awenly-directory' ), 'singular_name' => __( 'Enquiry', 'awenly-directory' ) ),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => 'edit.php?post_type=' . AWD_CPT,
		'supports'     => array( 'title', 'editor' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
}, 21 );

function awd_client_ip() {
	return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
}

add_action( 'template_redirect', function () {
	if ( empty( $_POST['awd_enquiry'] ) || ! is_singular( AWD_CPT ) ) {
		return;
	}
	$post = get_queried_object();
	$back = get_permalink( $post ) . '#contact';
	if ( ! isset( $_POST['awd_qnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_qnonce'] ) ), 'awd_enquiry_' . $post->ID ) ) {
		wp_safe_redirect( add_query_arg( 'awd_q', 'expired', $back ) );
		exit;
	}
	// Honeypot + minimum fill time.
	if ( ! empty( $_POST['awd_website_hp'] ) || ( time() - (int) ( $_POST['awd_t'] ?? 0 ) ) < 3 ) {
		wp_safe_redirect( add_query_arg( 'awd_q', 'sent', $back ) );
		exit;
	}
	$ip_key = 'awd_q_' . md5( awd_client_ip() );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		wp_safe_redirect( add_query_arg( 'awd_q', 'limit', $back ) );
		exit;
	}
	$name  = mb_substr( sanitize_text_field( wp_unslash( $_POST['awd_q_name'] ?? '' ) ), 0, 80 );
	$email = sanitize_email( wp_unslash( $_POST['awd_q_email'] ?? '' ) );
	$phone = mb_substr( sanitize_text_field( wp_unslash( $_POST['awd_q_phone'] ?? '' ) ), 0, 40 );
	$text  = mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST['awd_q_text'] ?? '' ) ) ), 0, 3000 );
	if ( ! $name || ! is_email( $email ) || mb_strlen( $text ) < 10 || empty( $_POST['awd_q_consent'] ) ) {
		wp_safe_redirect( add_query_arg( 'awd_q', 'invalid', $back ) );
		exit;
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$owner = get_userdata( (int) $post->post_author );
	$qid   = wp_insert_post( array(
		'post_type'    => 'awd_enquiry',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s → %s', $name, $post->post_title ),
		'post_content' => $text,
		'post_author'  => (int) $post->post_author,
		'post_parent'  => $post->ID,
	) );
	if ( $qid ) {
		update_post_meta( $qid, '_awd_q_email', $email );
		update_post_meta( $qid, '_awd_q_phone', $phone );
		update_post_meta( $qid, '_awd_q_name', $name );
	}
	update_post_meta( $post->ID, '_awd_enquiries', (int) get_post_meta( $post->ID, '_awd_enquiries', true ) + 1 );
	if ( $owner ) {
		wp_mail(
			$owner->user_email,
			sprintf( '[%s] New enquiry from %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $name ),
			sprintf( "Hi %s,\n\n%s sent an enquiry about \"%s\":\n\n%s\n\nEmail: %s\n%s\nJust reply to this email to answer.\n\n— Awenly Circle\n", $owner->display_name, $name, $post->post_title, $text, $email, $phone ? 'Phone: ' . $phone . "\n" : '' ),
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);
	}
	wp_safe_redirect( add_query_arg( 'awd_q', 'sent', $back ) );
	exit;
} );

function awd_render_enquiry_form( $post ) {
	if ( get_current_user_id() === (int) $post->post_author ) {
		return;
	}
	$u      = wp_get_current_user();
	$state  = sanitize_key( $_GET['awd_q'] ?? '' );
	$states = array(
		'sent'    => array( 'ok', __( 'Thank you! Your message was sent. You will get a reply by email.', 'awenly-directory' ) ),
		'invalid' => array( 'err', __( 'Please add your name, a valid email, a short message and tick the consent box.', 'awenly-directory' ) ),
		'limit'   => array( 'err', __( 'Too many messages from your connection. Please try again later.', 'awenly-directory' ) ),
		'expired' => array( 'err', __( 'The form expired. Please try again.', 'awenly-directory' ) ),
	);
	?>
	<section class="awd-contact" id="contact">
		<h2><?php esc_html_e( 'Send an enquiry', 'awenly-directory' ); ?></h2>
		<p class="awd-muted"><?php esc_html_e( 'No account needed. Your message goes straight to the inbox of this listing’s owner.', 'awenly-directory' ); ?></p>
		<?php if ( isset( $states[ $state ] ) ) : ?><div class="awd-notice awd-notice--<?php echo esc_attr( $states[ $state ][0] ); ?>"><?php echo esc_html( $states[ $state ][1] ); ?></div><?php endif; ?>
		<?php if ( 'sent' !== $state ) : ?>
		<form method="post" class="awd-contact__form">
			<?php wp_nonce_field( 'awd_enquiry_' . $post->ID, 'awd_qnonce' ); ?>
			<input type="hidden" name="awd_enquiry" value="1"><input type="hidden" name="awd_t" value="<?php echo (int) time(); ?>">
			<label class="awd-hp" aria-hidden="true">Website<input type="text" name="awd_website_hp" tabindex="-1" autocomplete="off"></label>
			<div class="awd-grid">
				<label class="awd-field"><span><?php esc_html_e( 'Your name *', 'awenly-directory' ); ?></span><input type="text" name="awd_q_name" required maxlength="80" value="<?php echo esc_attr( $u->exists() ? $u->display_name : '' ); ?>"></label>
				<label class="awd-field"><span><?php esc_html_e( 'Email *', 'awenly-directory' ); ?></span><input type="email" name="awd_q_email" required value="<?php echo esc_attr( $u->exists() ? $u->user_email : '' ); ?>"></label>
				<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Phone (optional)', 'awenly-directory' ); ?></span><input type="tel" name="awd_q_phone" maxlength="40"></label>
				<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Message *', 'awenly-directory' ); ?></span><textarea name="awd_q_text" rows="5" required minlength="10" maxlength="3000"></textarea></label>
			</div>
			<label class="awd-check"><input type="checkbox" name="awd_q_consent" value="1" required> <?php esc_html_e( 'I agree that my name and contact details are shared with this listing’s owner so they can reply.', 'awenly-directory' ); ?></label>
			<button type="submit" class="awd-btn"><?php esc_html_e( 'Send message', 'awenly-directory' ); ?></button>
		</form>
		<?php endif; ?>
	</section>
	<?php
}

add_filter( 'manage_awd_enquiry_posts_columns', function ( $c ) {
	$c['awd_from'] = __( 'From', 'awenly-directory' );
	return $c;
} );
add_action( 'manage_awd_enquiry_posts_custom_column', function ( $col, $id ) {
	if ( 'awd_from' === $col ) {
		echo esc_html( get_post_meta( $id, '_awd_q_name', true ) . ' <' . get_post_meta( $id, '_awd_q_email', true ) . '>' );
	}
}, 10, 2 );

/* ===================== Saved listings (favourites) ===================== */

function awd_saved_ids( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	return $user_id ? array_values( array_filter( array_map( 'absint', (array) get_user_meta( $user_id, '_awd_saved', true ) ) ) ) : array();
}

function awd_save_button( $post, $small = false ) {
	$saved = in_array( $post->ID, awd_saved_ids(), true );
	$url   = is_user_logged_in()
		? wp_nonce_url( add_query_arg( 'awd_save', $post->ID ), 'awd_save_' . $post->ID )
		: wp_login_url( get_permalink( $post ) );
	$label = $saved ? __( 'Saved', 'awenly-directory' ) : __( 'Save', 'awenly-directory' );
	return '<a class="awd-save' . ( $saved ? ' is-saved' : '' ) . ( $small ? ' awd-save--sm' : '' ) . '" href="' . esc_url( $url ) . '" rel="nofollow" title="' . esc_attr( $label ) . '" aria-label="' . esc_attr( $label ) . '"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7.5-4.6-9.5-9.3C1 8.2 3.2 4.5 7 4.5c2.1 0 3.6 1.2 5 3 1.4-1.8 2.9-3 5-3 3.8 0 6 3.7 4.5 7.2C19.5 16.4 12 21 12 21z"/></svg>' . ( $small ? '' : '<span>' . esc_html( $label ) . '</span>' ) . '</a>';
}

add_action( 'template_redirect', function () {
	if ( empty( $_GET['awd_save'] ) || ! is_user_logged_in() ) {
		return;
	}
	$id = absint( $_GET['awd_save'] );
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'awd_save_' . $id ) || AWD_CPT !== get_post_type( $id ) ) {
		return;
	}
	$ids = awd_saved_ids();
	$ids = in_array( $id, $ids, true ) ? array_values( array_diff( $ids, array( $id ) ) ) : array_merge( $ids, array( $id ) );
	update_user_meta( get_current_user_id(), '_awd_saved', array_slice( $ids, -200 ) );
	wp_safe_redirect( remove_query_arg( array( 'awd_save', '_wpnonce' ) ) );
	exit;
} );

function awd_render_saved_listings( $user_id ) {
	$ids = awd_saved_ids( $user_id );
	if ( ! $ids ) {
		return;
	}
	$posts = get_posts( array( 'post_type' => AWD_CPT, 'post__in' => $ids, 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => 'post__in' ) );
	if ( ! $posts ) {
		return;
	}
	echo '<section class="awd-saved" id="saved"><h3 class="awd-wrap__title">' . esc_html__( 'Saved listings', 'awenly-directory' ) . '</h3><div class="awd-grid-cards">';
	foreach ( $posts as $p ) {
		awd_render_card( $p );
	}
	echo '</div></section>';
}

/* ===================== Similar listings ===================== */

function awd_similar_listings( $post, $limit = 3 ) {
	$terms = wp_get_object_terms( $post->ID, AWD_TAX, array( 'fields' => 'ids' ) );
	$base  = array( 'post_type' => AWD_CPT, 'post_status' => 'publish', 'posts_per_page' => $limit, 'post__not_in' => array( $post->ID ), 'orderby' => 'rand', 'no_found_rows' => true );
	$found = array();
	if ( $terms && ! is_wp_error( $terms ) ) {
		$found = get_posts( $base + array( 'tax_query' => array( array( 'taxonomy' => AWD_TAX, 'terms' => $terms ) ) ) );
	}
	if ( count( $found ) < $limit && awd_meta( $post->ID, 'location' ) ) {
		$more  = get_posts( array_merge( $base, array(
			'posts_per_page' => $limit - count( $found ),
			'post__not_in'   => array_merge( array( $post->ID ), wp_list_pluck( $found, 'ID' ) ),
			'meta_query'     => array( array( 'key' => '_awenly_location', 'value' => awd_meta( $post->ID, 'location' ), 'compare' => 'LIKE' ) ),
		) ) );
		$found = array_merge( $found, $more );
	}
	return $found;
}

/* ===================== Share + QR ===================== */

function awd_render_share( $post ) {
	$url   = get_permalink( $post );
	$title = get_the_title( $post );
	$links = array(
		'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ) ),
		'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
		'x'        => array( 'X', 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ) ),
		'email'    => array( 'Email', 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ) ),
	);
	$icons = array(
		'whatsapp' => '<path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.6 2 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.1.1.7-.1 1.3z"/>',
		'facebook' => '<path fill="currentColor" d="M14 8h3V4h-3c-2.8 0-4 1.7-4 4.2V10H7v4h3v8h4v-8h3l1-4h-4V8.6c0-.4.3-.6.7-.6z"/>',
		'x'        => '<path d="M4 4l16 16M20 4L4 20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',
		'email'    => '<rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="m3 7 9 6 9-6" fill="none" stroke="currentColor" stroke-width="2"/>',
	);
	?>
	<div class="awd-share">
		<span><?php esc_html_e( 'Share', 'awenly-directory' ); ?></span>
		<?php foreach ( $links as $k => $l ) : ?>
			<a href="<?php echo esc_url( $l[1] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $l[0] ); ?>" title="<?php echo esc_attr( $l[0] ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $icons[ $k ]; // phpcs:ignore ?></svg></a>
		<?php endforeach; ?>
		<button type="button" data-awd-copy="<?php echo esc_attr( $url ); ?>" title="<?php esc_attr_e( 'Copy link', 'awenly-directory' ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'awenly-directory' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
		<details class="awd-qr"><summary title="<?php esc_attr_e( 'QR code', 'awenly-directory' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 3h8v8H3zm2 2v4h4V5zm8-2h8v8h-8zm2 2v4h4V5zM3 13h8v8H3zm2 2v4h4v-4zm8-2h3v3h-3zm3 3h3v3h-3zm3-3h2v3h-2zm-6 6h3v2h-3zm5 0h3v2h-3z"/></svg></summary>
			<div class="awd-qr__pop"><img loading="lazy" src="<?php echo esc_url( 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=10&data=' . rawurlencode( $url ) ); ?>" alt="<?php esc_attr_e( 'QR code for this listing', 'awenly-directory' ); ?>" width="200" height="200"><a href="<?php echo esc_url( 'https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&margin=20&format=png&data=' . rawurlencode( $url ) ); ?>" target="_blank" rel="noopener" download><?php esc_html_e( 'Download for print', 'awenly-directory' ); ?></a></div>
		</details>
	</div>
	<?php
}

/* ===================== Click tracking (works with page cache) ===================== */

add_action( 'wp_ajax_awd_track', 'awd_track_click' );
add_action( 'wp_ajax_nopriv_awd_track', 'awd_track_click' );
function awd_track_click() {
	$id   = absint( $_POST['id'] ?? 0 );
	$what = sanitize_key( $_POST['what'] ?? '' );
	if ( AWD_CPT === get_post_type( $id ) && in_array( $what, array( 'book', 'msg', 'web', 'service' ), true ) ) {
		$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) );
		if ( ! preg_match( '/bot|crawl|spider|headless/', $ua ) && get_current_user_id() !== (int) get_post_field( 'post_author', $id ) ) {
			$key = '_awd_clicks_' . ( 'service' === $what ? 'book' : $what );
			update_post_meta( $id, $key, (int) get_post_meta( $id, $key, true ) + 1 );
		}
	}
	wp_die( '', '', array( 'response' => 204 ) );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular( AWD_CPT ) ) {
		wp_enqueue_script( 'awenly-directory-single', AWD_URI . 'assets/single.js', array(), AWD_VERSION, true );
		wp_localize_script( 'awenly-directory-single', 'AWD', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'id' => get_queried_object_id(), 'copied' => __( 'Link copied', 'awenly-directory' ) ) );
	}
}, 10001 );

/* ===================== Follow / Connect (BuddyBoss) ===================== */

function awd_render_social_buttons( $user_id ) {
	if ( ! is_user_logged_in() || get_current_user_id() === (int) $user_id ) {
		return;
	}
	ob_start();
	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() && function_exists( 'bp_add_follow_button' ) ) {
		bp_add_follow_button( $user_id, get_current_user_id() );
	}
	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) && function_exists( 'bp_add_friend_button' ) ) {
		bp_add_friend_button( $user_id );
	}
	$html = trim( ob_get_clean() );
	if ( $html ) {
		echo '<div class="awd-bp-buttons">' . $html . '</div>'; // phpcs:ignore
	}
}

/* ===================== Featured listings ===================== */

function awd_is_featured( $post_id ) {
	return (bool) get_post_meta( $post_id, '_awd_featured', true );
}

add_action( 'post_submitbox_misc_actions', function ( $post ) {
	if ( AWD_CPT !== $post->post_type || ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	echo '<div class="misc-pub-section"><label><input type="checkbox" name="awd_featured" value="1" ' . checked( awd_is_featured( $post->ID ), true, false ) . '> ' . esc_html__( 'Featured (shown first in the directory)', 'awenly-directory' ) . '</label></div>';
} );
add_action( 'save_post_' . AWD_CPT, function ( $post_id ) {
	if ( isset( $_POST['awd_verified_nonce'] ) && current_user_can( 'edit_others_posts' ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_verified_nonce'] ) ), 'awd_verified' ) ) {
		update_post_meta( $post_id, '_awd_featured', empty( $_POST['awd_featured'] ) ? 0 : 1 );
	}
	if ( '' === get_post_meta( $post_id, '_awd_featured', true ) ) {
		update_post_meta( $post_id, '_awd_featured', 0 );
	}
	if ( '' === get_post_meta( $post_id, '_awd_type', true ) ) {
		update_post_meta( $post_id, '_awd_type', 'practitioner' );
	}
}, 6 );

/* ===================== Admin: stats page ===================== */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=' . AWD_CPT, __( 'Directory stats', 'awenly-directory' ), __( 'Stats', 'awenly-directory' ), 'edit_others_posts', 'awd-stats', function () {
		$posts = get_posts( array( 'post_type' => AWD_CPT, 'post_status' => array( 'publish', 'pending', 'draft' ), 'posts_per_page' => 500, 'no_found_rows' => true ) );
		$rows  = array();
		foreach ( $posts as $p ) {
			$r      = awd_rating( $p->ID );
			$rows[] = array(
				'p'     => $p,
				'views' => (int) get_post_meta( $p->ID, '_awd_views', true ),
				'book'  => (int) get_post_meta( $p->ID, '_awd_clicks_book', true ),
				'msg'   => (int) get_post_meta( $p->ID, '_awd_clicks_msg', true ),
				'web'   => (int) get_post_meta( $p->ID, '_awd_clicks_web', true ),
				'enq'   => (int) get_post_meta( $p->ID, '_awd_enquiries', true ),
				'r'     => $r,
			);
		}
		usort( $rows, function ( $a, $b ) {
			return $b['views'] <=> $a['views'];
		} );
		$types = awd_types();
		echo '<div class="wrap"><h1>' . esc_html__( 'Directory stats', 'awenly-directory' ) . '</h1><table class="widefat striped"><thead><tr>';
		foreach ( array( 'Listing', 'Type', 'Status', 'Views', 'Booking clicks', 'Message clicks', 'Website clicks', 'Enquiries', 'Rating', 'Featured', 'Verified' ) as $h ) {
			echo '<th>' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $rows as $x ) {
			$p = $x['p'];
			echo '<tr><td><a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '"><strong>' . esc_html( $p->post_title ) . '</strong></a></td><td>' . esc_html( $types[ awd_type( $p ) ]['label'] ) . '</td><td>' . esc_html( $p->post_status ) . '</td><td>' . (int) $x['views'] . '</td><td>' . (int) $x['book'] . '</td><td>' . (int) $x['msg'] . '</td><td>' . (int) $x['web'] . '</td><td>' . (int) $x['enq'] . '</td><td>' . ( $x['r']['count'] ? esc_html( number_format_i18n( $x['r']['avg'], 1 ) . ' ★ (' . $x['r']['count'] . ')' ) : '—' ) . '</td><td>' . ( awd_is_featured( $p->ID ) ? '★' : '—' ) . '</td><td>' . ( awd_is_verified( $p->ID ) ? '✔' : '—' ) . '</td></tr>';
		}
		if ( ! $rows ) {
			echo '<tr><td colspan="11">' . esc_html__( 'No listings yet.', 'awenly-directory' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	} );
} );

/* ===================== Structured data (SEO) ===================== */

add_action( 'wp_head', function () {
	if ( ! is_singular( AWD_CPT ) ) {
		return;
	}
	$post  = get_queried_object();
	$type  = awd_type( $post );
	$map   = array( 'practitioner' => 'ProfessionalService', 'centre' => 'HealthAndBeautyBusiness', 'venue' => 'EventVenue', 'shop' => 'Store', 'retreat' => 'Event', 'course' => 'Course' );
	$img   = get_the_post_thumbnail_url( $post, 'large' );
	$data  = array(
		'@context'    => 'https://schema.org',
		'@type'       => $map[ $type ],
		'name'        => get_the_title( $post ),
		'url'         => get_permalink( $post ),
		'description' => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : $post->post_content ), 40, '…' ),
	);
	if ( $img ) {
		$data['image'] = $img;
	}
	$loc = awd_meta( $post->ID, 'location' );
	if ( 'retreat' === $type ) {
		if ( awd_meta( $post->ID, 'date_start' ) ) {
			$data['startDate'] = awd_meta( $post->ID, 'date_start' );
		}
		if ( awd_meta( $post->ID, 'date_end' ) ) {
			$data['endDate'] = awd_meta( $post->ID, 'date_end' );
		}
		if ( $loc ) {
			$data['location'] = array( '@type' => 'Place', 'name' => $loc, 'address' => $loc );
		}
		$data['organizer'] = array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ) );
	} elseif ( 'course' === $type ) {
		$data['provider'] = array( '@type' => 'Organization', 'name' => get_the_author_meta( 'display_name', $post->post_author ) );
	} elseif ( $loc ) {
		$data['address'] = array( '@type' => 'PostalAddress', 'addressLocality' => $loc );
	}
	if ( awd_meta( $post->ID, 'fee' ) && in_array( $type, array( 'practitioner', 'centre', 'shop', 'venue' ), true ) ) {
		$data['priceRange'] = awd_meta( $post->ID, 'fee' );
	}
	$same = array_values( awd_social_links( (int) $post->post_author ) );
	if ( awd_meta( $post->ID, 'website' ) ) {
		array_unshift( $same, awd_meta( $post->ID, 'website' ) );
	}
	if ( $same ) {
		$data['sameAs'] = $same;
	}
	$r = awd_rating( $post->ID );
	if ( $r['count'] && 'course' !== $type && 'retreat' !== $type ) {
		$data['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => round( $r['avg'], 1 ), 'reviewCount' => $r['count'], 'bestRating' => 5, 'worstRating' => 1 );
	}
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
} );

/* ===================== Activity: new events of practitioners ===================== */

add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'tribe_events' !== $post->post_type || 'publish' !== $new || 'publish' === $old || get_post_meta( $post->ID, '_awd_activity_posted', true ) ) {
		return;
	}
	if ( ! function_exists( 'bp_activity_add' ) || ! bp_is_active( 'activity' ) || ! awd_get_user_listings( (int) $post->post_author, true ) ) {
		return;
	}
	$uid = (int) $post->post_author;
	$id  = bp_activity_add( array(
		'user_id'      => $uid,
		'component'    => 'members',
		'type'         => 'awd_event_published',
		'action'       => sprintf( __( '%1$s added a new event: %2$s', 'awenly-directory' ), bp_core_get_userlink( $uid ), '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $post->post_title ) . '</a>' ),
		'primary_link' => get_permalink( $post ),
		'item_id'      => $post->ID,
	) );
	if ( $id ) {
		update_post_meta( $post->ID, '_awd_activity_posted', $id );
	}
}, 20, 3 );
