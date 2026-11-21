<?php
/**
 * "My Practice" tab on BuddyBoss profiles: practitioners create and edit their own listing.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile tab. Own profile: always (to create / edit). Other profiles: only when they have a published listing.
 */
add_action( 'bp_setup_nav', function () {
	if ( ! function_exists( 'bp_core_new_nav_item' ) || ! function_exists( 'bp_displayed_user_id' ) ) {
		return;
	}
	$displayed = (int) bp_displayed_user_id();
	if ( ! $displayed ) {
		return;
	}
	$is_own  = is_user_logged_in() && get_current_user_id() === $displayed;
	$listing = awd_get_user_listing( $displayed );
	if ( ! $is_own && ! ( $listing && 'publish' === $listing->post_status ) ) {
		return;
	}
	bp_core_new_nav_item( array(
		'name'                    => $is_own ? __( 'My Practice', 'awenly-directory' ) : __( 'Practice', 'awenly-directory' ),
		'slug'                    => 'practice',
		'position'                => 25,
		'screen_function'         => 'awd_practice_screen',
		'default_subnav_slug'     => 'practice',
		'show_for_displayed_user' => true,
	) );
}, 100 );

function awd_practice_screen() {
	add_action( 'bp_template_content', 'awd_practice_content' );
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

/**
 * Save handler (runs before the page renders).
 */
add_action( 'bp_actions', function () {
	if ( empty( $_POST['awd_action'] ) || 'save_listing' !== $_POST['awd_action'] ) {
		return;
	}
	if ( ! is_user_logged_in() || ! function_exists( 'bp_displayed_user_id' ) ) {
		return;
	}
	$user_id = get_current_user_id();
	if ( (int) bp_displayed_user_id() !== $user_id ) {
		return;
	}
	if ( ! isset( $_POST['awd_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_nonce'] ) ), 'awd_save_listing_' . $user_id ) ) {
		wp_die( esc_html__( 'Your session expired. Please go back and try again.', 'awenly-directory' ) );
	}

	$redirect = trailingslashit( bp_loggedin_user_domain() ) . 'practice/';
	$title    = sanitize_text_field( wp_unslash( $_POST['awd_title'] ?? '' ) );
	$about    = wp_kses_post( wp_unslash( $_POST['awd_about'] ?? '' ) );

	if ( '' === $title || '' === trim( wp_strip_all_tags( $about ) ) ) {
		wp_safe_redirect( add_query_arg( 'awd', 'missing', $redirect ) );
		exit;
	}

	$listing = awd_get_user_listing( $user_id );
	$hide    = ! empty( $_POST['awd_hide'] );
	$approved = $listing && get_post_meta( $listing->ID, '_awd_approved', true );

	if ( $hide ) {
		$status = 'draft';
	} elseif ( $approved || ( $listing && 'publish' === $listing->post_status ) ) {
		$status = 'publish';
	} else {
		$status = 'pending';
	}

	$data = array(
		'post_type'    => AWD_CPT,
		'post_title'   => mb_substr( $title, 0, 120 ),
		'post_content' => $about,
		'post_excerpt' => mb_substr( sanitize_text_field( wp_unslash( $_POST['awd_tagline'] ?? '' ) ), 0, 90 ),
		'post_status'  => $status,
		'post_author'  => $user_id,
	);

	if ( $listing ) {
		$data['ID'] = $listing->ID;
		$post_id    = wp_update_post( $data, true );
	} else {
		$post_id = wp_insert_post( $data, true );
	}
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_safe_redirect( add_query_arg( 'awd', 'error', $redirect ) );
		exit;
	}

	foreach ( awd_fields() as $key => $f ) {
		$raw = wp_unslash( $_POST[ 'awd_' . $key ] ?? '' );
		if ( 'url' === $f['type'] ) {
			$val = esc_url_raw( trim( $raw ), array( 'http', 'https' ) );
		} elseif ( 'select' === $f['type'] ) {
			$val = array_key_exists( $raw, $f['options'] ) ? $raw : '';
		} else {
			$val = sanitize_text_field( $raw );
			if ( ! empty( $f['max'] ) ) {
				$val = mb_substr( $val, 0, (int) $f['max'] );
			}
		}
		update_post_meta( $post_id, '_awenly_' . $key, $val );
	}

	// Specialties: max 4, only existing terms.
	$terms = array_map( 'absint', (array) ( $_POST['awd_specialties'] ?? array() ) );
	$terms = array_slice( array_filter( $terms, function ( $id ) {
		return (bool) get_term( $id, AWD_TAX );
	} ), 0, 4 );
	wp_set_object_terms( $post_id, $terms, AWD_TAX );

	// Photo (optional).
	if ( ! empty( $_FILES['awd_photo']['name'] ) ) {
		$file = $_FILES['awd_photo']; // phpcs:ignore
		$ok   = in_array( wp_check_filetype( $file['name'] )['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) && (int) $file['size'] <= 6 * MB_IN_BYTES;
		if ( $ok ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$att = media_handle_upload( 'awd_photo', $post_id );
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $post_id, $att );
			}
		}
	}

	do_action( 'awd_listing_saved', $post_id );

	$flag = $listing ? 'saved' : 'submitted';
	wp_safe_redirect( add_query_arg( 'awd', $flag, $redirect ) );
	exit;
} );

/**
 * Tab content.
 */
function awd_practice_content() {
	$displayed = (int) bp_displayed_user_id();
	$is_own    = is_user_logged_in() && get_current_user_id() === $displayed;
	$listing   = awd_get_user_listing( $displayed );

	echo '<div class="awd-wrap">';
	if ( ! $is_own ) {
		if ( $listing && 'publish' === $listing->post_status ) {
			awd_render_card( $listing, true );
		}
		echo '</div>';
		return;
	}

	$notice = sanitize_key( $_GET['awd'] ?? '' );
	$notices = array(
		'submitted' => array( 'ok', __( 'Thank you! Your listing was sent for review. We will let you know by email when it is live.', 'awenly-directory' ) ),
		'saved'     => array( 'ok', __( 'Your listing has been saved.', 'awenly-directory' ) ),
		'missing'   => array( 'err', __( 'Please fill in your practice name and the "About" section.', 'awenly-directory' ) ),
		'error'     => array( 'err', __( 'Something went wrong. Please try again.', 'awenly-directory' ) ),
		'event_submitted'     => array( 'ok', __( 'Thank you! Your event was sent for review.', 'awenly-directory' ) ),
		'event_invalid'       => array( 'err', __( 'Please add a title, a future date and a start time for your event.', 'awenly-directory' ) ),
		'event_needs_listing' => array( 'err', __( 'Your listing needs to be approved before you can add events.', 'awenly-directory' ) ),
	);
	if ( isset( $notices[ $notice ] ) ) {
		printf( '<div class="awd-notice awd-notice--%s">%s</div>', esc_attr( $notices[ $notice ][0] ), esc_html( $notices[ $notice ][1] ) );
	}

	// Status banner.
	if ( $listing ) {
		$status_map = array(
			'publish' => array( 'live', __( 'Live in the directory', 'awenly-directory' ) ),
			'pending' => array( 'pending', __( 'Waiting for approval', 'awenly-directory' ) ),
			'draft'   => array( 'hidden', __( 'Hidden from the directory', 'awenly-directory' ) ),
		);
		$st = $status_map[ $listing->post_status ] ?? $status_map['draft'];
		echo '<div class="awd-status awd-status--' . esc_attr( $st[0] ) . '"><span>' . esc_html( $st[1] ) . '</span>';
		if ( 'publish' === $listing->post_status ) {
			$r = awd_rating( $listing->ID );
			echo ' <a href="' . esc_url( get_permalink( $listing ) ) . '">' . esc_html__( 'View my listing →', 'awenly-directory' ) . '</a>';
			echo '<em class="awd-stats">' . esc_html( sprintf( __( '%1$d views · %2$s ★ (%3$d reviews)', 'awenly-directory' ), (int) get_post_meta( $listing->ID, '_awd_views', true ), number_format_i18n( $r['avg'], 1 ), $r['count'] ) ) . ( awd_is_verified( $listing->ID ) ? ' · ' . esc_html__( 'Verified', 'awenly-directory' ) : '' ) . '</em>';
		}
		echo '</div>';
	} else {
		echo '<div class="awd-intro"><h3>' . esc_html__( 'List your practice — it’s free', 'awenly-directory' ) . '</h3><p>' . esc_html__( 'Create your listing in the Awenly Circle practitioner directory. After a quick review it will be visible to everyone looking for a therapist, coach or healer.', 'awenly-directory' ) . '</p></div>';
	}

	$id       = $listing ? $listing->ID : 0;
	$selected = $id ? wp_get_object_terms( $id, AWD_TAX, array( 'fields' => 'ids' ) ) : array();
	$terms    = get_terms( array( 'taxonomy' => AWD_TAX, 'hide_empty' => false ) );
	?>
	<form class="awd-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'awd_save_listing_' . get_current_user_id(), 'awd_nonce' ); ?>
		<input type="hidden" name="awd_action" value="save_listing">

		<div class="awd-grid">
			<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Practice / display name *', 'awenly-directory' ); ?></span>
				<input type="text" name="awd_title" required maxlength="120" value="<?php echo esc_attr( $listing ? $listing->post_title : wp_get_current_user()->display_name ); ?>"></label>

			<?php foreach ( awd_fields() as $key => $f ) :
				$val = $id ? awd_meta( $id, $key ) : '';
				if ( 'tagline' === $key ) {
					$val = $listing ? $listing->post_excerpt : '';
				} ?>
				<label class="awd-field"><span><?php echo esc_html( $f['label'] ); ?></span>
				<?php if ( 'select' === $f['type'] ) : ?>
					<select name="awd_<?php echo esc_attr( $key ); ?>">
						<option value=""><?php esc_html_e( 'Choose…', 'awenly-directory' ); ?></option>
						<?php foreach ( $f['options'] as $ov => $ol ) : ?>
							<option value="<?php echo esc_attr( $ov ); ?>" <?php selected( $val, $ov ); ?>><?php echo esc_html( $ol ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input type="<?php echo esc_attr( $f['type'] ); ?>" name="awd_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>" placeholder="<?php echo esc_attr( $f['placeholder'] ?? '' ); ?>" <?php echo ! empty( $f['max'] ) ? 'maxlength="' . (int) $f['max'] . '"' : ''; ?>>
				<?php endif; ?>
				</label>
			<?php endforeach; ?>
		</div>

		<fieldset class="awd-field awd-field--wide awd-specialties">
			<legend><?php esc_html_e( 'Specialisations (up to 4)', 'awenly-directory' ); ?></legend>
			<?php foreach ( (array) $terms as $t ) : ?>
				<label class="awd-chip"><input type="checkbox" name="awd_specialties[]" value="<?php echo (int) $t->term_id; ?>" <?php checked( in_array( $t->term_id, $selected, true ) ); ?>><span><?php echo esc_html( $t->name ); ?></span></label>
			<?php endforeach; ?>
		</fieldset>

		<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'About you & your work *', 'awenly-directory' ); ?></span>
			<textarea name="awd_about" rows="8" required placeholder="<?php esc_attr_e( 'Who do you work with? What happens in a session? What can people expect?', 'awenly-directory' ); ?>"><?php echo esc_textarea( $listing ? $listing->post_content : '' ); ?></textarea></label>

		<div class="awd-field awd-field--wide awd-photo">
			<span><?php esc_html_e( 'Listing photo', 'awenly-directory' ); ?></span>
			<?php if ( $id && has_post_thumbnail( $id ) ) {
				echo get_the_post_thumbnail( $id, 'thumbnail' );
			} ?>
			<input type="file" name="awd_photo" accept="image/jpeg,image/png,image/webp">
			<small><?php esc_html_e( 'JPG, PNG or WebP, max 6 MB. Without a photo, your profile picture is used.', 'awenly-directory' ); ?></small>
		</div>

		<?php if ( $listing && get_post_meta( $listing->ID, '_awd_approved', true ) ) : ?>
			<label class="awd-check"><input type="checkbox" name="awd_hide" value="1" <?php checked( 'draft', $listing->post_status ); ?>> <?php esc_html_e( 'Temporarily hide my listing from the directory', 'awenly-directory' ); ?></label>
		<?php endif; ?>

		<button type="submit" class="awd-btn"><?php echo $listing ? esc_html__( 'Save listing', 'awenly-directory' ) : esc_html__( 'Submit for review', 'awenly-directory' ); ?></button>
	</form>
	<?php
	awd_render_event_form( $listing );
	echo '</div>';
}
