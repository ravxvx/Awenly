<?php
/**
 * Reviews & ratings for practitioner listings.
 *
 * Stored as comments of type "awd_review" (rating in comment meta "awd_rating").
 * Practitioner replies are child comments of type "awd_reply".
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	add_post_type_support( AWD_CPT, 'comments' );
}, 25 );

// Listings always accept reviews (we render our own form, not the theme's comment form).
add_filter( 'comments_open', function ( $open, $post_id ) {
	return AWD_CPT === get_post_type( $post_id ) ? true : $open;
}, 10, 2 );

/**
 * Keep reviews out of the theme's default comment list on listing pages.
 */
add_filter( 'comments_template', function ( $tpl ) {
	return is_singular( AWD_CPT ) ? AWD_DIR . 'includes/empty.php' : $tpl;
} );

function awd_rating( $post_id ) {
	return array(
		'avg'   => (float) get_post_meta( $post_id, '_awd_rating_avg', true ),
		'count' => (int) get_post_meta( $post_id, '_awd_rating_count', true ),
	);
}

function awd_recount_rating( $post_id ) {
	$reviews = get_comments( array(
		'post_id' => $post_id,
		'type'    => 'awd_review',
		'status'  => 'approve',
		'fields'  => 'ids',
	) );
	$sum = 0;
	foreach ( $reviews as $cid ) {
		$sum += (int) get_comment_meta( $cid, 'awd_rating', true );
	}
	$count = count( $reviews );
	update_post_meta( $post_id, '_awd_rating_count', $count );
	update_post_meta( $post_id, '_awd_rating_avg', $count ? round( $sum / $count, 2 ) : 0 );
}

function awd_stars( $avg, $count = null, $small = false ) {
	$avg  = max( 0, min( 5, (float) $avg ) );
	$pct  = $avg / 5 * 100;
	$html = '<span class="awd-stars' . ( $small ? ' awd-stars--sm' : '' ) . '" aria-label="' . esc_attr( sprintf( __( 'Rated %s out of 5', 'awenly-directory' ), number_format_i18n( $avg, 1 ) ) ) . '">';
	$html .= '<span class="awd-stars__bg">★★★★★</span><span class="awd-stars__fg" style="width:' . esc_attr( $pct ) . '%">★★★★★</span></span>';
	if ( null !== $count ) {
		$html .= ' <span class="awd-stars__txt">' . ( $count ? esc_html( number_format_i18n( $avg, 1 ) ) . ' <small>(' . esc_html( sprintf( _n( '%d review', '%d reviews', $count, 'awenly-directory' ), $count ) ) . ')</small>' : '<small>' . esc_html__( 'No reviews yet', 'awenly-directory' ) . '</small>' ) . '</span>';
	}
	return $html;
}

/**
 * Has the user already reviewed this listing (any status)?
 */
function awd_user_review( $post_id, $user_id ) {
	if ( ! $user_id ) {
		return null;
	}
	$c = get_comments( array(
		'post_id' => $post_id,
		'type'    => 'awd_review',
		'user_id' => $user_id,
		'status'  => 'all',
		'number'  => 1,
	) );
	return $c ? $c[0] : null;
}

/**
 * Handle: new review, practitioner reply, report.
 */
add_action( 'template_redirect', function () {
	if ( empty( $_POST['awd_review_action'] ) || ! is_singular( AWD_CPT ) ) {
		return;
	}
	$post    = get_queried_object();
	$back    = get_permalink( $post ) . '#reviews';
	$action  = sanitize_key( $_POST['awd_review_action'] );
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		wp_safe_redirect( wp_login_url( $back ) );
		exit;
	}
	if ( ! isset( $_POST['awd_rnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['awd_rnonce'] ) ), 'awd_review_' . $post->ID ) ) {
		wp_die( esc_html__( 'Your session expired. Please go back and try again.', 'awenly-directory' ) );
	}
	$user = wp_get_current_user();

	if ( 'review' === $action ) {
		$rating = (int) ( $_POST['awd_rating'] ?? 0 );
		$text   = trim( sanitize_textarea_field( wp_unslash( $_POST['awd_text'] ?? '' ) ) );
		if ( (int) $post->post_author === $user_id || awd_user_review( $post->ID, $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'awd_r', 'not_allowed', $back ) );
			exit;
		}
		if ( $rating < 1 || $rating > 5 || mb_strlen( $text ) < 10 ) {
			wp_safe_redirect( add_query_arg( 'awd_r', 'invalid', $back ) );
			exit;
		}
		$cid = wp_insert_comment( array(
			'comment_post_ID'      => $post->ID,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_content'      => mb_substr( $text, 0, 2000 ),
			'comment_type'         => 'awd_review',
			'user_id'              => $user_id,
			'comment_approved'     => 0,
		) );
		if ( $cid ) {
			add_comment_meta( $cid, 'awd_rating', $rating, true );
			wp_mail(
				get_option( 'admin_email' ),
				sprintf( '[%s] New review to approve: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $post->post_title ),
				sprintf( "%s rated %s %d/5:\n\n%s\n\nApprove or delete it here:\n%s\n", $user->display_name, $post->post_title, $rating, $text, admin_url( 'edit-comments.php?comment_status=moderated&comment_type=awd_review' ) )
			);
		}
		wp_safe_redirect( add_query_arg( 'awd_r', 'thanks', $back ) );
		exit;
	}

	if ( 'reply' === $action ) {
		$parent = get_comment( (int) ( $_POST['awd_parent'] ?? 0 ) );
		$text   = trim( sanitize_textarea_field( wp_unslash( $_POST['awd_text'] ?? '' ) ) );
		$exists = $parent ? get_comments( array( 'parent' => $parent->comment_ID, 'type' => 'awd_reply', 'count' => true ) ) : 1;
		if ( ! $parent || (int) $parent->comment_post_ID !== $post->ID || 'awd_review' !== $parent->comment_type || (int) $post->post_author !== $user_id || $exists || mb_strlen( $text ) < 2 ) {
			wp_safe_redirect( add_query_arg( 'awd_r', 'not_allowed', $back ) );
			exit;
		}
		wp_insert_comment( array(
			'comment_post_ID'      => $post->ID,
			'comment_parent'       => $parent->comment_ID,
			'comment_author'       => $user->display_name,
			'comment_author_email' => $user->user_email,
			'comment_content'      => mb_substr( $text, 0, 1500 ),
			'comment_type'         => 'awd_reply',
			'user_id'              => $user_id,
			'comment_approved'     => 1,
		) );
		wp_safe_redirect( add_query_arg( 'awd_r', 'replied', $back ) );
		exit;
	}

	if ( 'report' === $action ) {
		$c = get_comment( (int) ( $_POST['awd_comment'] ?? 0 ) );
		if ( $c && (int) $c->comment_post_ID === $post->ID ) {
			$reporters = (array) get_comment_meta( $c->comment_ID, 'awd_reported_by', false );
			if ( ! in_array( $user_id, array_map( 'intval', $reporters ), true ) ) {
				add_comment_meta( $c->comment_ID, 'awd_reported_by', $user_id );
				wp_mail(
					get_option( 'admin_email' ),
					sprintf( '[%s] Review reported on %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $post->post_title ),
					sprintf( "%s reported this review by %s:\n\n%s\n\nReview it here:\n%s\n", $user->display_name, $c->comment_author, $c->comment_content, admin_url( 'comment.php?action=editcomment&c=' . $c->comment_ID ) )
				);
			}
		}
		wp_safe_redirect( add_query_arg( 'awd_r', 'reported', $back ) );
		exit;
	}
} );

/**
 * Recount + notify the practitioner when a review is approved.
 */
add_action( 'transition_comment_status', function ( $new, $old, $comment ) {
	if ( 'awd_review' !== $comment->comment_type ) {
		return;
	}
	awd_recount_rating( (int) $comment->comment_post_ID );
	if ( 'approved' === $new && 'approved' !== $old ) {
		$post   = get_post( (int) $comment->comment_post_ID );
		$author = $post ? get_userdata( (int) $post->post_author ) : null;
		if ( $author ) {
			wp_mail(
				$author->user_email,
				sprintf( '[%s] You have a new review', wp_specialchars_decode( get_bloginfo( 'name' ) ) ),
				sprintf( "Hi %s,\n\n%s left a %d-star review on your listing:\n\n\"%s\"\n\nYou can reply to it here:\n%s\n", $author->display_name, $comment->comment_author, (int) get_comment_meta( $comment->comment_ID, 'awd_rating', true ), $comment->comment_content, get_permalink( $post ) . '#reviews' )
			);
		}
	}
}, 10, 3 );
add_action( 'deleted_comment', function ( $cid, $comment ) {
	if ( $comment && 'awd_review' === $comment->comment_type ) {
		awd_recount_rating( (int) $comment->comment_post_ID );
	}
}, 10, 2 );

/**
 * Admin: Reviews submenu + rating column in Comments.
 */
add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=' . AWD_CPT, __( 'Reviews', 'awenly-directory' ), __( 'Reviews', 'awenly-directory' ), 'moderate_comments', 'edit-comments.php?comment_type=awd_review' );
} );
add_filter( 'admin_comment_types_dropdown', function ( $types ) {
	$types['awd_review'] = __( 'Practitioner reviews', 'awenly-directory' );
	return $types;
} );
add_filter( 'comment_text', function ( $text, $comment = null ) {
	if ( is_admin() && $comment && 'awd_review' === $comment->comment_type ) {
		$r      = (int) get_comment_meta( $comment->comment_ID, 'awd_rating', true );
		$rep    = count( (array) get_comment_meta( $comment->comment_ID, 'awd_reported_by', false ) );
		$prefix = '<strong>' . str_repeat( '★', $r ) . str_repeat( '☆', 5 - $r ) . '</strong>' . ( $rep ? ' <span style="color:#b32d2e">⚑ ' . (int) $rep . ' report(s)</span>' : '' ) . '<br>';
		return $prefix . $text;
	}
	return $text;
}, 10, 2 );

/**
 * Front-end reviews section.
 */
function awd_render_reviews( $post ) {
	$rating   = awd_rating( $post->ID );
	$user_id  = get_current_user_id();
	$is_owner = $user_id && (int) $post->post_author === $user_id;
	$mine     = awd_user_review( $post->ID, $user_id );
	$reviews  = get_comments( array( 'post_id' => $post->ID, 'type' => 'awd_review', 'status' => 'approve', 'orderby' => 'comment_date_gmt', 'order' => 'DESC' ) );
	$notice   = sanitize_key( $_GET['awd_r'] ?? '' );
	$notices  = array(
		'thanks'      => __( 'Thank you! Your review will appear after a quick check.', 'awenly-directory' ),
		'invalid'     => __( 'Please choose a star rating and write at least a few words.', 'awenly-directory' ),
		'not_allowed' => __( 'That action is not possible.', 'awenly-directory' ),
		'replied'     => __( 'Your reply has been posted.', 'awenly-directory' ),
		'reported'    => __( 'Thank you — the review has been reported to the moderators.', 'awenly-directory' ),
	);
	?>
	<section class="awd-reviews" id="reviews">
		<header class="awd-reviews__head">
			<h2><?php esc_html_e( 'Reviews', 'awenly-directory' ); ?></h2>
			<div><?php echo awd_stars( $rating['avg'], $rating['count'] ); // phpcs:ignore ?></div>
		</header>
		<?php if ( isset( $notices[ $notice ] ) ) : ?>
			<div class="awd-notice <?php echo in_array( $notice, array( 'invalid', 'not_allowed' ), true ) ? 'awd-notice--err' : 'awd-notice--ok'; ?>"><?php echo esc_html( $notices[ $notice ] ); ?></div>
		<?php endif; ?>

		<?php if ( $reviews ) : ?>
			<ul class="awd-review-list">
				<?php foreach ( $reviews as $c ) :
					$r       = (int) get_comment_meta( $c->comment_ID, 'awd_rating', true );
					$replies = get_comments( array( 'parent' => $c->comment_ID, 'type' => 'awd_reply', 'status' => 'approve' ) );
					?>
					<li class="awd-review">
						<div class="awd-review__top">
							<?php echo get_avatar( $c->user_id ? $c->user_id : $c->comment_author_email, 44 ); ?>
							<div><strong><?php echo esc_html( $c->comment_author ); ?></strong><span><?php echo awd_stars( $r, null, true ); // phpcs:ignore ?> · <?php echo esc_html( human_time_diff( strtotime( $c->comment_date_gmt ), time() ) ); ?> <?php esc_html_e( 'ago', 'awenly-directory' ); ?></span></div>
						</div>
						<p><?php echo nl2br( esc_html( $c->comment_content ) ); ?></p>
						<?php foreach ( $replies as $rp ) : ?>
							<div class="awd-review__reply"><strong><?php esc_html_e( 'Reply from the practitioner', 'awenly-directory' ); ?></strong><p><?php echo nl2br( esc_html( $rp->comment_content ) ); ?></p></div>
						<?php endforeach; ?>
						<div class="awd-review__actions">
							<?php if ( $is_owner && ! $replies ) : ?>
								<details><summary><?php esc_html_e( 'Reply', 'awenly-directory' ); ?></summary>
									<form method="post"><?php wp_nonce_field( 'awd_review_' . $post->ID, 'awd_rnonce' ); ?>
										<input type="hidden" name="awd_review_action" value="reply"><input type="hidden" name="awd_parent" value="<?php echo (int) $c->comment_ID; ?>">
										<textarea name="awd_text" rows="3" required maxlength="1500"></textarea>
										<button class="awd-btn awd-btn--sm" type="submit"><?php esc_html_e( 'Post reply', 'awenly-directory' ); ?></button></form>
								</details>
							<?php endif; ?>
							<?php if ( $user_id && (int) $c->user_id !== $user_id ) : ?>
								<form method="post" class="awd-report"><?php wp_nonce_field( 'awd_review_' . $post->ID, 'awd_rnonce' ); ?>
									<input type="hidden" name="awd_review_action" value="report"><input type="hidden" name="awd_comment" value="<?php echo (int) $c->comment_ID; ?>">
									<button type="submit"><?php esc_html_e( 'Report', 'awenly-directory' ); ?></button></form>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="awd-muted"><?php esc_html_e( 'No reviews yet. Have you had a session? Share your experience.', 'awenly-directory' ); ?></p>
		<?php endif; ?>

		<?php if ( ! $user_id ) : ?>
			<p class="awd-muted"><a href="<?php echo esc_url( wp_login_url( get_permalink( $post ) . '#reviews' ) ); ?>"><?php esc_html_e( 'Log in', 'awenly-directory' ); ?></a> <?php esc_html_e( 'to leave a review.', 'awenly-directory' ); ?></p>
		<?php elseif ( $is_owner ) : ?>
			<p class="awd-muted"><?php esc_html_e( 'You can reply to each review on your listing once.', 'awenly-directory' ); ?></p>
		<?php elseif ( $mine ) : ?>
			<p class="awd-muted"><?php echo '0' === (string) $mine->comment_approved ? esc_html__( 'Your review is waiting for approval.', 'awenly-directory' ) : esc_html__( 'You have already reviewed this practitioner. Thank you!', 'awenly-directory' ); ?></p>
		<?php else : ?>
			<form class="awd-review-form" method="post">
				<?php wp_nonce_field( 'awd_review_' . $post->ID, 'awd_rnonce' ); ?>
				<input type="hidden" name="awd_review_action" value="review">
				<h3><?php esc_html_e( 'Write a review', 'awenly-directory' ); ?></h3>
				<fieldset class="awd-rate" aria-label="<?php esc_attr_e( 'Your rating', 'awenly-directory' ); ?>">
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<input type="radio" id="awd-star-<?php echo (int) $i; ?>" name="awd_rating" value="<?php echo (int) $i; ?>" required><label for="awd-star-<?php echo (int) $i; ?>" title="<?php echo (int) $i; ?>/5">★</label>
					<?php endfor; ?>
				</fieldset>
				<textarea name="awd_text" rows="4" required minlength="10" maxlength="2000" placeholder="<?php esc_attr_e( 'How was your experience?', 'awenly-directory' ); ?>"></textarea>
				<button class="awd-btn" type="submit"><?php esc_html_e( 'Submit review', 'awenly-directory' ); ?></button>
			</form>
		<?php endif; ?>
	</section>
	<?php
}
