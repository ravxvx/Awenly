<?php
/**
 * "My Listings" tab on BuddyBoss profiles: members create and edit their own listings
 * (practitioner, centre, retreat, venue, shop, course).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWD_MAX_LISTINGS', 10 );
define( 'AWD_MAX_GALLERY', 8 );
define( 'AWD_MAX_SERVICES', 10 );

/**
 * Profile tab. Own profile: always. Other profiles: only when they have a published listing.
 */
add_action( 'bp_setup_nav', function () {
	if ( ! function_exists( 'bp_core_new_nav_item' ) || ! function_exists( 'bp_displayed_user_id' ) ) {
		return;
	}
	$displayed = (int) bp_displayed_user_id();
	if ( ! $displayed ) {
		return;
	}
	$is_own = is_user_logged_in() && get_current_user_id() === $displayed;
	if ( ! $is_own && ! awd_get_user_listings( $displayed, true ) ) {
		return;
	}
	bp_core_new_nav_item( array(
		'name'                    => $is_own ? __( 'My Listings', 'awenly-directory' ) : __( 'Listings', 'awenly-directory' ),
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
 * Handle one uploaded image field (single file array). Returns attachment ID or error flag string.
 */
function awd_handle_image( $file, $post_id ) {
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return in_array( (int) $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? 'photo_big' : 'photo_error';
	}
	if ( (int) $file['size'] > 10 * MB_IN_BYTES ) {
		return 'photo_big';
	}
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( ! in_array( $check['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		return 'photo_type';
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$key            = 'awd_tmp_upload';
	$_FILES[ $key ] = $file;
	$att            = media_handle_upload( $key, $post_id, array(), array( 'test_form' => false ) );
	unset( $_FILES[ $key ] );
	return is_wp_error( $att ) ? 'photo_error' : (int) $att;
}

/**
 * Save handler (runs before the page renders).
 */
add_action( 'bp_actions', function () {
	if ( empty( $_POST['awd_action'] ) || ! is_user_logged_in() || ! function_exists( 'bp_displayed_user_id' ) ) {
		return;
	}
	$action  = sanitize_key( $_POST['awd_action'] );
	$user_id = get_current_user_id();
	if ( ! in_array( $action, array( 'save_listing', 'delete_listing' ), true ) || (int) bp_displayed_user_id() !== $user_id ) {
		return;
	}
	if ( ! isset( $_POST['awd_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_nonce'] ) ), 'awd_save_listing_' . $user_id ) ) {
		wp_die( esc_html__( 'Your session expired. Please go back and try again.', 'awenly-directory' ) );
	}
	$base    = trailingslashit( bp_loggedin_user_domain() ) . 'practice/';
	$id      = absint( $_POST['awd_listing_id'] ?? 0 );
	$listing = $id ? get_post( $id ) : null;
	if ( $listing && ( AWD_CPT !== $listing->post_type || (int) $listing->post_author !== $user_id ) ) {
		wp_safe_redirect( add_query_arg( 'awd', 'error', $base ) );
		exit;
	}

	if ( 'delete_listing' === $action ) {
		if ( $listing ) {
			wp_trash_post( $listing->ID );
		}
		wp_safe_redirect( add_query_arg( 'awd', 'deleted', $base ) );
		exit;
	}

	if ( ! $listing && count( awd_get_user_listings( $user_id ) ) >= AWD_MAX_LISTINGS ) {
		wp_safe_redirect( add_query_arg( 'awd', 'limit', $base ) );
		exit;
	}

	$type  = sanitize_key( $_POST['awd_type'] ?? 'practitioner' );
	$type  = array_key_exists( $type, awd_types() ) ? $type : 'practitioner';
	$title = sanitize_text_field( wp_unslash( $_POST['awd_title'] ?? '' ) );
	$about = wp_kses_post( wp_unslash( $_POST['awd_about'] ?? '' ) );
	$back  = $listing ? add_query_arg( 'edit', $listing->ID, $base ) : add_query_arg( 'new', $type, $base );

	if ( '' === $title || '' === trim( wp_strip_all_tags( $about ) ) ) {
		wp_safe_redirect( add_query_arg( 'awd', 'missing', $back ) );
		exit;
	}

	$hide     = ! empty( $_POST['awd_hide'] );
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
		wp_safe_redirect( add_query_arg( 'awd', 'error', $back ) );
		exit;
	}
	update_post_meta( $post_id, '_awd_type', $type );

	foreach ( awd_fields() as $key => $f ) {
		if ( 'tagline' === $key ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ 'awd_' . $key ] ?? '' );
		if ( ! awd_field_applies( $f, $type ) ) {
			$val = '';
		} elseif ( 'url' === $f['type'] ) {
			$val = esc_url_raw( trim( $raw ), array( 'http', 'https' ) );
		} elseif ( 'select' === $f['type'] ) {
			$val = array_key_exists( $raw, $f['options'] ) ? $raw : '';
		} elseif ( 'date' === $f['type'] ) {
			$val = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
		} else {
			$val = sanitize_text_field( $raw );
			if ( ! empty( $f['max'] ) ) {
				$val = mb_substr( $val, 0, (int) $f['max'] );
			}
		}
		update_post_meta( $post_id, '_awenly_' . $key, $val );
	}

	// Video (YouTube / Vimeo link).
	$video = esc_url_raw( trim( wp_unslash( $_POST['awd_video'] ?? '' ) ), array( 'https', 'http' ) );
	if ( $video && ! preg_match( '#(youtube\.com|youtu\.be|vimeo\.com)/#i', $video ) ) {
		$video = '';
	}
	update_post_meta( $post_id, '_awenly_video', $video );

	// Services & prices.
	$services = array();
	$sv       = (array) ( $_POST['awd_services'] ?? array() ); // phpcs:ignore
	foreach ( array_slice( $sv, 0, AWD_MAX_SERVICES ) as $row ) {
		$name = mb_substr( sanitize_text_field( wp_unslash( $row['name'] ?? '' ) ), 0, 80 );
		if ( '' === $name ) {
			continue;
		}
		$services[] = array(
			'name'     => $name,
			'duration' => mb_substr( sanitize_text_field( wp_unslash( $row['duration'] ?? '' ) ), 0, 30 ),
			'price'    => mb_substr( sanitize_text_field( wp_unslash( $row['price'] ?? '' ) ), 0, 30 ),
			'desc'     => mb_substr( sanitize_text_field( wp_unslash( $row['desc'] ?? '' ) ), 0, 200 ),
			'url'      => esc_url_raw( trim( wp_unslash( $row['url'] ?? '' ) ), array( 'http', 'https' ) ),
		);
	}
	update_post_meta( $post_id, '_awd_services', $services );

	// Categories: max 4, only existing terms.
	$terms = array_map( 'absint', (array) ( $_POST['awd_specialties'] ?? array() ) );
	$terms = array_slice( array_filter( $terms, function ( $tid ) {
		return (bool) get_term( $tid, AWD_TAX );
	} ), 0, 4 );
	wp_set_object_terms( $post_id, $terms, AWD_TAX );

	// Main photo.
	$photo_flag = '';
	if ( ! empty( $_FILES['awd_photo']['name'] ) ) {
		$r = awd_handle_image( $_FILES['awd_photo'], $post_id ); // phpcs:ignore
		if ( is_int( $r ) ) {
			set_post_thumbnail( $post_id, $r );
		} else {
			$photo_flag = $r;
		}
	} elseif ( ! empty( $_POST['awd_remove_photo'] ) ) {
		delete_post_thumbnail( $post_id );
	}

	// Gallery.
	$gallery = array_map( 'absint', (array) get_post_meta( $post_id, '_awd_gallery', true ) );
	$remove  = array_map( 'absint', (array) ( $_POST['awd_gallery_remove'] ?? array() ) );
	$gallery = array_values( array_diff( array_filter( $gallery ), $remove ) );
	if ( ! empty( $_FILES['awd_gallery']['name'] ) && is_array( $_FILES['awd_gallery']['name'] ) ) {
		$g = $_FILES['awd_gallery']; // phpcs:ignore
		foreach ( $g['name'] as $i => $n ) {
			if ( '' === $n || count( $gallery ) >= AWD_MAX_GALLERY ) {
				continue;
			}
			$r = awd_handle_image( array( 'name' => $n, 'type' => $g['type'][ $i ], 'tmp_name' => $g['tmp_name'][ $i ], 'error' => $g['error'][ $i ], 'size' => $g['size'][ $i ] ), $post_id );
			if ( is_int( $r ) ) {
				$gallery[] = $r;
			} elseif ( ! $photo_flag ) {
				$photo_flag = $r;
			}
		}
	}
	update_post_meta( $post_id, '_awd_gallery', $gallery );

	do_action( 'awd_listing_saved', $post_id );

	$flag = $photo_flag ? $photo_flag : ( $listing ? 'saved' : 'submitted' );
	wp_safe_redirect( add_query_arg( array( 'awd' => $flag, 'edit' => $post_id ), $base ) );
	exit;
} );

/**
 * Tab content.
 */
function awd_practice_content() {
	$displayed = (int) bp_displayed_user_id();
	$is_own    = is_user_logged_in() && get_current_user_id() === $displayed;

	echo '<div class="awd-wrap">';
	if ( ! $is_own ) {
		echo '<div class="awd-grid-cards">';
		foreach ( awd_get_user_listings( $displayed, true ) as $l ) {
			awd_render_card( $l );
		}
		echo '</div></div>';
		return;
	}

	$base    = trailingslashit( bp_loggedin_user_domain() ) . 'practice/';
	$notice  = sanitize_key( $_GET['awd'] ?? '' );
	$notices = array(
		'submitted'           => array( 'ok', __( 'Thank you! Your listing was sent for review. We will let you know by email when it is live.', 'awenly-directory' ) ),
		'saved'               => array( 'ok', __( 'Your listing has been saved.', 'awenly-directory' ) ),
		'deleted'             => array( 'ok', __( 'The listing was removed.', 'awenly-directory' ) ),
		'limit'               => array( 'err', __( 'You have reached the maximum number of listings.', 'awenly-directory' ) ),
		'missing'             => array( 'err', __( 'Please fill in the name and the "About" section.', 'awenly-directory' ) ),
		'error'               => array( 'err', __( 'Something went wrong. Please try again.', 'awenly-directory' ) ),
		'photo_big'           => array( 'err', __( 'Your details were saved, but a photo is too large. Please choose a smaller image.', 'awenly-directory' ) ),
		'photo_type'          => array( 'err', __( 'Your details were saved, but photos must be JPG, PNG or WebP images.', 'awenly-directory' ) ),
		'photo_error'         => array( 'err', __( 'Your details were saved, but a photo could not be uploaded. Please try again.', 'awenly-directory' ) ),
		'event_submitted'     => array( 'ok', __( 'Thank you! Your event was sent for review.', 'awenly-directory' ) ),
		'event_invalid'       => array( 'err', __( 'Please add a title, a future date and a start time for your event.', 'awenly-directory' ) ),
		'event_needs_listing' => array( 'err', __( 'Your listing needs to be approved before you can add events.', 'awenly-directory' ) ),
	);
	if ( isset( $notices[ $notice ] ) ) {
		printf( '<div class="awd-notice awd-notice--%s">%s</div>', esc_attr( $notices[ $notice ][0] ), esc_html( $notices[ $notice ][1] ) );
	}

	$listings = awd_get_user_listings( $displayed );
	$edit_id  = absint( $_GET['edit'] ?? 0 );
	$new_type = sanitize_key( $_GET['new'] ?? '' );
	$editing  = null;
	foreach ( $listings as $l ) {
		if ( $l->ID === $edit_id ) {
			$editing = $l;
		}
	}

	if ( $editing || array_key_exists( $new_type, awd_types() ) ) {
		echo '<a class="awd-back-link" href="' . esc_url( $base ) . '">← ' . esc_html__( 'All my listings', 'awenly-directory' ) . '</a>';
		awd_render_listing_form( $editing, $editing ? awd_type( $editing ) : $new_type );
		echo '</div>';
		return;
	}

	if ( $listings ) {
		echo '<h3 class="awd-wrap__title">' . esc_html__( 'My listings', 'awenly-directory' ) . '</h3><ul class="awd-mylist">';
		$status_map = array(
			'publish' => array( 'live', __( 'Live', 'awenly-directory' ) ),
			'pending' => array( 'pending', __( 'Waiting for approval', 'awenly-directory' ) ),
			'draft'   => array( 'hidden', __( 'Hidden', 'awenly-directory' ) ),
		);
		$types = awd_types();
		foreach ( $listings as $l ) {
			$st = $status_map[ $l->post_status ] ?? $status_map['draft'];
			$r  = awd_rating( $l->ID );
			$t  = awd_type( $l );
			echo '<li class="awd-mylist__item"><div class="awd-mylist__img">' . awd_photo( $l, 'thumbnail' ) . '</div><div class="awd-mylist__body">'; // phpcs:ignore
			echo '<span class="awd-mylist__type">' . esc_html( $types[ $t ]['label'] ) . '</span><strong>' . esc_html( get_the_title( $l ) ) . '</strong>';
			echo '<span class="awd-status--' . esc_attr( $st[0] ) . '"><span>' . esc_html( $st[1] ) . '</span></span>';
			if ( 'publish' === $l->post_status ) {
				echo '<em class="awd-stats">' . esc_html( sprintf(
					/* translators: views, enquiries, booking clicks, rating, reviews */
					__( '%1$d views · %2$d enquiries · %3$d booking clicks · %4$s ★ (%5$d)', 'awenly-directory' ),
					(int) get_post_meta( $l->ID, '_awd_views', true ),
					(int) get_post_meta( $l->ID, '_awd_enquiries', true ),
					(int) get_post_meta( $l->ID, '_awd_clicks_book', true ),
					number_format_i18n( $r['avg'], 1 ),
					$r['count']
				) ) . '</em>';
			}
			echo '</div><div class="awd-mylist__actions"><a class="awd-btn awd-btn--sm" href="' . esc_url( add_query_arg( 'edit', $l->ID, $base ) ) . '">' . esc_html__( 'Edit', 'awenly-directory' ) . '</a>';
			if ( 'publish' === $l->post_status ) {
				echo '<a class="awd-btn awd-btn--sm awd-btn--ghost" href="' . esc_url( get_permalink( $l ) ) . '">' . esc_html__( 'View', 'awenly-directory' ) . '</a>';
			}
			echo '</div></li>';
		}
		echo '</ul>';
	} else {
		echo '<div class="awd-intro"><h3>' . esc_html__( 'Add your listing — it’s free', 'awenly-directory' ) . '</h3><p>' . esc_html__( 'Practitioners, centres, retreats, venues, shops and courses can all be listed in the Awenly Circle directory. After a quick review your listing will be visible to everyone.', 'awenly-directory' ) . '</p></div>';
	}

	if ( count( $listings ) < AWD_MAX_LISTINGS ) {
		echo '<h3 class="awd-wrap__title">' . esc_html__( 'Add a new listing', 'awenly-directory' ) . '</h3><div class="awd-typepick">';
		foreach ( awd_types() as $k => $t ) {
			echo '<a href="' . esc_url( add_query_arg( 'new', $k, $base ) ) . '">' . awd_icon( $t['icon'] ) . '<strong>' . esc_html( $t['label'] ) . '</strong><small>' . esc_html( $t['desc'] ) . '</small></a>'; // phpcs:ignore
		}
		echo '</div>';
	}

	$published = awd_get_user_listings( $displayed, true );
	awd_render_event_form( $published ? $published[0] : null );
	awd_render_saved_listings( $displayed );
	echo '</div>';
}

/**
 * The listing form.
 */
function awd_render_listing_form( $listing, $type ) {
	$types    = awd_types();
	$id       = $listing ? $listing->ID : 0;
	$selected = $id ? wp_get_object_terms( $id, AWD_TAX, array( 'fields' => 'ids' ) ) : array();
	$terms    = get_terms( array( 'taxonomy' => AWD_TAX, 'hide_empty' => false ) );
	$services = $id ? (array) get_post_meta( $id, '_awd_services', true ) : array();
	$gallery  = $id ? array_filter( array_map( 'absint', (array) get_post_meta( $id, '_awd_gallery', true ) ) ) : array();
	$name_def = ( ! $listing && 'practitioner' === $type ) ? wp_get_current_user()->display_name : '';
	if ( $listing ) {
		$sts = array( 'publish' => __( 'Live in the directory', 'awenly-directory' ), 'pending' => __( 'Waiting for approval', 'awenly-directory' ), 'draft' => __( 'Hidden from the directory', 'awenly-directory' ) );
		echo '<div class="awd-status awd-status--' . esc_attr( 'publish' === $listing->post_status ? 'live' : ( 'pending' === $listing->post_status ? 'pending' : 'hidden' ) ) . '"><span>' . esc_html( $sts[ $listing->post_status ] ?? '' ) . '</span>';
		if ( 'publish' === $listing->post_status ) {
			echo ' <a href="' . esc_url( get_permalink( $listing ) ) . '">' . esc_html__( 'View listing →', 'awenly-directory' ) . '</a>';
		}
		echo '</div>';
	}
	?>
	<form class="awd-form" method="post" enctype="multipart/form-data" data-awd-form>
		<?php wp_nonce_field( 'awd_save_listing_' . get_current_user_id(), 'awd_nonce' ); ?>
		<input type="hidden" name="awd_action" value="save_listing">
		<input type="hidden" name="awd_listing_id" value="<?php echo (int) $id; ?>">

		<div class="awd-grid">
			<label class="awd-field"><span><?php esc_html_e( 'Listing type', 'awenly-directory' ); ?></span>
				<select name="awd_type" data-awd-type>
					<?php foreach ( $types as $k => $t ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $type, $k ); ?>><?php echo esc_html( $t['label'] ); ?></option><?php endforeach; ?>
				</select></label>
			<label class="awd-field"><span><?php esc_html_e( 'Name *', 'awenly-directory' ); ?></span>
				<input type="text" name="awd_title" required maxlength="120" value="<?php echo esc_attr( $listing ? $listing->post_title : $name_def ); ?>"></label>

			<?php foreach ( awd_fields() as $key => $f ) :
				$val = $id ? awd_meta( $id, $key ) : '';
				if ( 'tagline' === $key ) {
					$val = $listing ? $listing->post_excerpt : '';
				}
				$tattr = empty( $f['types'] ) ? '' : ' data-types="' . esc_attr( implode( ' ', $f['types'] ) ) . '"';
				$hide  = awd_field_applies( $f, $type ) ? '' : ' hidden';
				?>
				<label class="awd-field"<?php echo $tattr . $hide; // phpcs:ignore ?>><span><?php echo esc_html( $f['label'] ); ?></span>
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
			<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'Welcome video (YouTube or Vimeo link)', 'awenly-directory' ); ?></span>
				<input type="url" name="awd_video" value="<?php echo esc_attr( $id ? awd_meta( $id, 'video' ) : '' ); ?>" placeholder="https://www.youtube.com/watch?v=…"></label>
		</div>

		<fieldset class="awd-field awd-field--wide awd-specialties">
			<legend><?php esc_html_e( 'Categories (up to 4)', 'awenly-directory' ); ?></legend>
			<?php foreach ( (array) $terms as $t ) : ?>
				<label class="awd-chip"><input type="checkbox" name="awd_specialties[]" value="<?php echo (int) $t->term_id; ?>" <?php checked( in_array( $t->term_id, $selected, true ) ); ?>><span><?php echo esc_html( $t->name ); ?></span></label>
			<?php endforeach; ?>
		</fieldset>

		<label class="awd-field awd-field--wide"><span><?php esc_html_e( 'About *', 'awenly-directory' ); ?></span>
			<textarea name="awd_about" rows="8" required placeholder="<?php esc_attr_e( 'Who is it for? What happens there? What can people expect?', 'awenly-directory' ); ?>"><?php echo esc_textarea( $listing ? $listing->post_content : '' ); ?></textarea></label>

		<fieldset class="awd-field awd-field--wide awd-services-edit">
			<legend><?php esc_html_e( 'Services, tickets or products & prices', 'awenly-directory' ); ?></legend>
			<div data-awd-services>
				<?php
				$rows = $services ? $services : array( array() );
				foreach ( $rows as $i => $row ) :
					?>
					<div class="awd-service-row" data-awd-service>
						<input type="text" name="awd_services[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $row['name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Name, e.g. Reiki session', 'awenly-directory' ); ?>" maxlength="80">
						<input type="text" name="awd_services[<?php echo (int) $i; ?>][duration]" value="<?php echo esc_attr( $row['duration'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Duration, e.g. 60 min', 'awenly-directory' ); ?>" maxlength="30">
						<input type="text" name="awd_services[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( $row['price'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Price, e.g. €50', 'awenly-directory' ); ?>" maxlength="30">
						<input type="text" name="awd_services[<?php echo (int) $i; ?>][desc]" value="<?php echo esc_attr( $row['desc'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Short description (optional)', 'awenly-directory' ); ?>" maxlength="200">
						<input type="url" name="awd_services[<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr( $row['url'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Booking / buy link (optional)', 'awenly-directory' ); ?>">
						<button type="button" class="awd-service-del" data-awd-service-del aria-label="<?php esc_attr_e( 'Remove', 'awenly-directory' ); ?>">×</button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="awd-btn awd-btn--ghost awd-btn--sm" data-awd-service-add><?php esc_html_e( '+ Add another', 'awenly-directory' ); ?></button>
		</fieldset>

		<div class="awd-field awd-field--wide awd-photo">
			<span><?php esc_html_e( 'Main photo or logo', 'awenly-directory' ); ?></span>
			<div class="awd-photo__row">
				<div class="awd-photo__preview" data-awd-preview><?php
					if ( $id && has_post_thumbnail( $id ) ) {
						echo get_the_post_thumbnail( $id, 'medium' );
					} else {
						echo get_avatar( get_current_user_id(), 240 );
					}
				?></div>
				<div class="awd-photo__ctrl">
					<label class="awd-btn awd-btn--ghost awd-btn--sm awd-photo__pick">
						<input type="file" name="awd_photo" accept="image/*" data-awd-photo hidden>
						<?php echo $id && has_post_thumbnail( $id ) ? esc_html__( 'Change photo', 'awenly-directory' ) : esc_html__( 'Upload a photo', 'awenly-directory' ); ?>
					</label>
					<?php if ( $id && has_post_thumbnail( $id ) ) : ?>
						<label class="awd-check awd-photo__remove"><input type="checkbox" name="awd_remove_photo" value="1"> <?php esc_html_e( 'Remove photo', 'awenly-directory' ); ?></label>
					<?php endif; ?>
					<small data-awd-photo-msg><?php esc_html_e( 'A clear portrait or logo works best. Large photos are resized automatically.', 'awenly-directory' ); ?></small>
				</div>
			</div>
		</div>

		<div class="awd-field awd-field--wide awd-gallery-edit">
			<span><?php echo esc_html( sprintf( __( 'Gallery (up to %d photos)', 'awenly-directory' ), AWD_MAX_GALLERY ) ); ?></span>
			<?php if ( $gallery ) : ?>
				<div class="awd-gallery-edit__grid">
					<?php foreach ( $gallery as $att ) : ?>
						<label><?php echo wp_get_attachment_image( $att, 'thumbnail' ); ?><span><input type="checkbox" name="awd_gallery_remove[]" value="<?php echo (int) $att; ?>"> <?php esc_html_e( 'Remove', 'awenly-directory' ); ?></span></label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( count( $gallery ) < AWD_MAX_GALLERY ) : ?>
				<label class="awd-btn awd-btn--ghost awd-btn--sm awd-photo__pick"><input type="file" name="awd_gallery[]" accept="image/*" multiple data-awd-gallery hidden><?php esc_html_e( 'Add photos', 'awenly-directory' ); ?></label>
				<small class="awd-muted" data-awd-gallery-msg></small>
			<?php endif; ?>
		</div>

		<?php if ( $listing && get_post_meta( $listing->ID, '_awd_approved', true ) ) : ?>
			<label class="awd-check"><input type="checkbox" name="awd_hide" value="1" <?php checked( 'draft', $listing->post_status ); ?>> <?php esc_html_e( 'Temporarily hide this listing from the directory', 'awenly-directory' ); ?></label>
		<?php endif; ?>

		<div class="awd-form__foot">
			<button type="submit" class="awd-btn"><?php echo $listing ? esc_html__( 'Save listing', 'awenly-directory' ) : esc_html__( 'Submit for review', 'awenly-directory' ); ?></button>
		</div>
	</form>
	<?php if ( $listing ) : ?>
		<form method="post" class="awd-delete" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this listing?', 'awenly-directory' ) ); ?>');">
			<?php wp_nonce_field( 'awd_save_listing_' . get_current_user_id(), 'awd_nonce' ); ?>
			<input type="hidden" name="awd_action" value="delete_listing"><input type="hidden" name="awd_listing_id" value="<?php echo (int) $listing->ID; ?>">
			<button type="submit"><?php esc_html_e( 'Remove this listing', 'awenly-directory' ); ?></button>
		</form>
	<?php endif; ?>
	<?php
}
