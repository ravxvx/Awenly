<?php
/**
 * Moderation: notify admin about new listings, notify practitioner on approval, assign profile type.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( AWD_CPT !== $post->post_type || $new === $old ) {
		return;
	}

	// New submission waiting for review → email the site admin.
	if ( 'pending' === $new ) {
		$author = get_userdata( (int) $post->post_author );
		wp_mail(
			get_option( 'admin_email' ),
			sprintf( '[%s] New practitioner listing to review: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $post->post_title ),
			sprintf(
				"A new practitioner listing is waiting for approval.\n\nName: %s\nSubmitted by: %s (%s)\n\nReview and publish it here:\n%s\n",
				$post->post_title,
				$author ? $author->display_name : '-',
				$author ? $author->user_email : '-',
				admin_url( 'post.php?post=' . $post->ID . '&action=edit' )
			)
		);
	}

	// Approved → remember, give the "Practitioner" profile type, email the practitioner.
	if ( 'publish' === $new && ! get_post_meta( $post->ID, '_awd_approved', true ) ) {
		update_post_meta( $post->ID, '_awd_approved', time() );
		$uid = (int) $post->post_author;
		if ( $uid && function_exists( 'bp_set_member_type' ) && function_exists( 'bp_get_member_type_object' ) && bp_get_member_type_object( 'practitioner' ) ) {
			bp_set_member_type( $uid, 'practitioner', true );
		}
		$author = get_userdata( $uid );
		if ( $author && ! user_can( $author, 'manage_options' ) ) {
			wp_mail(
				$author->user_email,
				sprintf( '[%s] Your practitioner listing is live', wp_specialchars_decode( get_bloginfo( 'name' ) ) ),
				sprintf(
					"Hi %s,\n\nGood news — your listing \"%s\" is now live in the Awenly Circle practitioner directory:\n%s\n\nYou can update it any time from the \"My Practice\" tab on your profile.\n\nWelcome to the circle!\n",
					$author->display_name,
					$post->post_title,
					get_permalink( $post )
				)
			);
		}
	}
}, 10, 3 );

/**
 * Pending count bubble on the admin menu.
 */
add_action( 'admin_menu', function () {
	global $menu;
	$count = (int) wp_count_posts( AWD_CPT )->pending;
	if ( ! $count || ! is_array( $menu ) ) {
		return;
	}
	foreach ( $menu as $k => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=' . AWD_CPT === $item[2] ) {
			$menu[ $k ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $count . '</span></span>';
		}
	}
}, 999 );

/**
 * Author column so you can see who submitted each listing.
 */
add_filter( 'manage_' . AWD_CPT . '_posts_columns', function ( $cols ) {
	$cols['awd_owner'] = __( 'Practitioner (member)', 'awenly-directory' );
	return $cols;
} );
add_action( 'manage_' . AWD_CPT . '_posts_custom_column', function ( $col, $post_id ) {
	if ( 'awd_owner' === $col ) {
		$u = get_userdata( (int) get_post_field( 'post_author', $post_id ) );
		echo $u ? esc_html( $u->display_name . ' — ' . $u->user_email ) : '—';
	}
}, 10, 2 );
