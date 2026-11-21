<?php
/**
 * Directory page (/practitioners/), listing page and styles.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'awenly-directory', AWD_URI . 'assets/directory.css', array(), AWD_VERSION );
}, 10000 );

/**
 * Use our templates for the directory and single listings.
 */
add_filter( 'template_include', function ( $template ) {
	if ( is_post_type_archive( AWD_CPT ) || is_tax( AWD_TAX ) ) {
		return AWD_DIR . 'includes/template-directory.php';
	}
	if ( is_singular( AWD_CPT ) ) {
		return AWD_DIR . 'includes/template-single.php';
	}
	return $template;
}, 99 );

add_filter( 'body_class', function ( $c ) {
	if ( is_post_type_archive( AWD_CPT ) || is_tax( AWD_TAX ) || is_singular( AWD_CPT ) ) {
		$c[] = 'awd-page';
	}
	return $c;
} );

/**
 * Current filters from the query string (supports the homepage chips ?s_practitioner=slug).
 */
function awd_current_filters() {
	$f = array(
		'q'         => sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) ),
		'specialty' => sanitize_title( wp_unslash( $_GET['specialty'] ?? '' ) ),
		'mode'      => sanitize_text_field( wp_unslash( $_GET['mode'] ?? '' ) ),
		'location'  => sanitize_text_field( wp_unslash( $_GET['location'] ?? '' ) ),
		'verified'  => ! empty( $_GET['verified'] ),
		'sort'      => in_array( $_GET['sort'] ?? '', array( 'rating', 'new' ), true ) ? sanitize_key( $_GET['sort'] ) : '',
	);
	if ( ! empty( $_GET['s_practitioner'] ) ) {
		$sp = sanitize_title( wp_unslash( $_GET['s_practitioner'] ) );
		if ( term_exists( $sp, AWD_TAX ) ) {
			$f['specialty'] = $sp;
		} else {
			$f['q'] = sanitize_text_field( wp_unslash( $_GET['s_practitioner'] ) );
		}
	}
	if ( is_tax( AWD_TAX ) ) {
		$f['specialty'] = get_queried_object()->slug;
	}
	if ( ! array_key_exists( $f['mode'], awd_fields()['mode']['options'] ) ) {
		$f['mode'] = '';
	}
	return $f;
}

function awd_query( $filters, $paged = 1, $extra = array() ) {
	$args = array(
		'post_type'      => AWD_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'paged'          => max( 1, (int) $paged ),
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	);
	if ( 'new' === $filters['sort'] ) {
		$args['orderby'] = array( 'date' => 'DESC' );
	}
	if ( $filters['q'] ) {
		$args['s'] = $filters['q'];
	}
	if ( $filters['specialty'] ) {
		$args['tax_query'] = array( array( 'taxonomy' => AWD_TAX, 'field' => 'slug', 'terms' => $filters['specialty'] ) );
	}
	$meta = array();
	if ( $filters['mode'] ) {
		$modes  = 'Both' === $filters['mode'] ? array( 'Both' ) : array( $filters['mode'], 'Both' );
		$meta[] = array( 'key' => '_awenly_mode', 'value' => $modes, 'compare' => 'IN' );
	}
	if ( $filters['location'] ) {
		$meta[] = array( 'key' => '_awenly_location', 'value' => $filters['location'], 'compare' => 'LIKE' );
	}
	if ( ! empty( $filters['verified'] ) ) {
		$meta[] = array( 'key' => '_awd_verified', 'value' => '1' );
	}
	if ( 'rating' === $filters['sort'] ) {
		$meta['awd_avg'] = array( 'relation' => 'OR', 'awd_avg_set' => array( 'key' => '_awd_rating_avg', 'type' => 'DECIMAL(4,2)' ), array( 'key' => '_awd_rating_avg', 'compare' => 'NOT EXISTS' ) );
		$args['orderby'] = array( 'awd_avg_set' => 'DESC', 'date' => 'DESC' );
	}
	if ( $meta ) {
		$args['meta_query'] = $meta;
	}
	return new WP_Query( array_merge( $args, $extra ) );
}

/**
 * Card used in the directory and on profile tabs.
 */
function awd_render_card( $post, $wide = false ) {
	$terms = wp_get_object_terms( $post->ID, AWD_TAX );
	$mode  = awd_meta( $post->ID, 'mode' );
	$modes = awd_fields()['mode']['options'];
	?>
	<article class="awd-card<?php echo $wide ? ' awd-card--wide' : ''; ?>">
		<a class="awd-card__photo" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo awd_photo( $post, 'medium_large' ); // phpcs:ignore ?></a>
		<div class="awd-card__body">
			<h3><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a><?php echo awd_is_verified( $post->ID ) ? awd_verified_badge( true ) : ''; // phpcs:ignore ?></h3>
			<?php $rt = awd_rating( $post->ID ); if ( $rt['count'] ) : ?><div class="awd-card__rating"><?php echo awd_stars( $rt['avg'], $rt['count'], true ); // phpcs:ignore ?></div><?php endif; ?>
			<?php if ( $post->post_excerpt ) : ?><p class="awd-card__tag"><?php echo esc_html( $post->post_excerpt ); ?></p><?php endif; ?>
			<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
				<div class="awd-chips"><?php foreach ( array_slice( $terms, 0, 3 ) as $t ) : ?><span><?php echo esc_html( $t->name ); ?></span><?php endforeach; ?></div>
			<?php endif; ?>
			<ul class="awd-card__meta">
				<?php if ( awd_meta( $post->ID, 'location' ) ) : ?><li><?php echo awd_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( awd_meta( $post->ID, 'location' ) ); ?></li><?php endif; ?>
				<?php if ( $mode ) : ?><li><?php echo awd_icon( 'video' ); // phpcs:ignore ?><?php echo esc_html( $modes[ $mode ] ?? $mode ); ?></li><?php endif; ?>
				<?php if ( awd_meta( $post->ID, 'fee' ) ) : ?><li><?php echo awd_icon( 'tag' ); // phpcs:ignore ?><?php echo esc_html( awd_meta( $post->ID, 'fee' ) ); ?></li><?php endif; ?>
			</ul>
			<a class="awd-card__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php esc_html_e( 'View profile', 'awenly-directory' ); ?> →</a>
		</div>
	</article>
	<?php
}

function awd_icon( $name ) {
	$i = array(
		'pin'   => '<path d="M12 22s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="10" r="2.5"/>',
		'video' => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="M16 10l6-3v10l-6-3z"/>',
		'tag'   => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'lang'  => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
		'cert'  => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/>',
		'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/>',
		'msg'   => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
		'cal'   => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'user'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
	);
	return isset( $i[ $name ] ) ? '<svg class="awd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $i[ $name ] . '</svg>' : '';
}

/**
 * Point the theme's homepage/practitioner links at the directory and "My Practice".
 */
add_filter( 'theme_mod_awenly_practitioners_url', function ( $v ) {
	return $v ? $v : awd_directory_url();
} );
