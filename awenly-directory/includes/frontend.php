<?php
/**
 * Directory page (/practitioners/), listing page and styles.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'awenly-directory', AWD_URI . 'assets/directory.css', array(), AWD_VERSION );
	if ( is_user_logged_in() && function_exists( 'bp_is_user' ) && bp_is_user() ) {
		wp_enqueue_script( 'awenly-directory-photo', AWD_URI . 'assets/photo.js', array(), AWD_VERSION, true );
	}
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
		'type'      => array_key_exists( $_GET['type'] ?? '', awd_types() ) ? sanitize_key( $_GET['type'] ) : '',
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
	if ( ! empty( $filters['type'] ) ) {
		$meta[] = 'practitioner' === $filters['type']
			? array( 'relation' => 'OR', array( 'key' => '_awd_type', 'value' => 'practitioner' ), array( 'key' => '_awd_type', 'compare' => 'NOT EXISTS' ), array( 'key' => '_awd_type', 'value' => '' ) )
			: array( 'key' => '_awd_type', 'value' => $filters['type'] );
	}
	if ( ! empty( $filters['verified'] ) ) {
		$meta[] = array( 'key' => '_awd_verified', 'value' => '1' );
	}
	if ( '' === $filters['sort'] && empty( $extra['fields'] ) ) {
		$meta['awd_feat'] = array( 'relation' => 'OR', 'awd_feat_set' => array( 'key' => '_awd_featured', 'type' => 'NUMERIC' ), array( 'key' => '_awd_featured', 'compare' => 'NOT EXISTS' ) );
		$args['orderby']  = array( 'awd_feat_set' => 'DESC', 'menu_order' => 'ASC', 'date' => 'DESC' );
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
	$url   = get_permalink( $post );
	$rt    = awd_rating( $post->ID );
	$type  = awd_type( $post );
	$types = awd_types();
	$dates = awd_date_range( $post->ID );
	?>
	<article class="awd-card awd-card--<?php echo esc_attr( $type ); ?><?php echo $wide ? ' awd-card--wide' : ''; ?>">
		<div class="awd-card__cover" aria-hidden="true"></div>
		<?php echo awd_save_button( $post, true ); // phpcs:ignore ?>
		<?php if ( awd_is_featured( $post->ID ) ) : ?><span class="awd-card__featured"><?php esc_html_e( 'Featured', 'awenly-directory' ); ?></span><?php endif; ?>
		<?php if ( 'practitioner' !== $type ) : ?><span class="awd-card__type"><?php echo awd_icon( $types[ $type ]['icon'] ); // phpcs:ignore ?><?php echo esc_html( $types[ $type ]['label'] ); ?></span><?php endif; ?>
		<a class="awd-card__avatar" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true"><?php echo awd_photo( $post, 'medium' ); // phpcs:ignore ?></a>
		<div class="awd-card__body">
			<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a><?php echo awd_is_verified( $post->ID ) ? awd_verified_badge( true ) : ''; // phpcs:ignore ?></h3>
			<?php if ( $post->post_excerpt ) : ?><p class="awd-card__tag"><?php echo esc_html( $post->post_excerpt ); ?></p><?php endif; ?>
			<div class="awd-card__rating"><?php echo awd_stars( $rt['avg'], $rt['count'], true ); // phpcs:ignore ?></div>
			<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
				<div class="awd-chips"><?php foreach ( array_slice( $terms, 0, 3 ) as $t ) : ?><span><?php echo esc_html( $t->name ); ?></span><?php endforeach; ?></div>
			<?php endif; ?>
			<ul class="awd-card__meta">
				<?php if ( $dates ) : ?><li><?php echo awd_icon( 'cal' ); // phpcs:ignore ?><?php echo esc_html( $dates ); ?></li><?php endif; ?>
				<?php if ( awd_meta( $post->ID, 'location' ) ) : ?><li><?php echo awd_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( awd_meta( $post->ID, 'location' ) ); ?></li><?php endif; ?>
				<?php if ( $mode ) : ?><li><?php echo awd_icon( 'video' ); // phpcs:ignore ?><?php echo esc_html( $modes[ $mode ] ?? $mode ); ?></li><?php endif; ?>
				<?php if ( awd_meta( $post->ID, 'fee' ) ) : ?><li><?php echo awd_icon( 'tag' ); // phpcs:ignore ?><?php echo esc_html( awd_meta( $post->ID, 'fee' ) ); ?></li><?php endif; ?>
			</ul>
			<div class="awd-card__actions">
				<a class="awd-btn awd-btn--sm" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View profile', 'awenly-directory' ); ?></a>
				<?php $msg = awd_message_url( $post ); if ( $msg ) : ?>
					<a class="awd-btn awd-btn--sm awd-btn--ghost" href="<?php echo esc_url( $msg ); ?>"><?php echo awd_icon( 'msg' ); // phpcs:ignore ?><?php esc_html_e( 'Message', 'awenly-directory' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

/**
 * "12–15 Mar 2027" style range for retreats/courses ('' when unset).
 */
function awd_date_range( $post_id ) {
	$a = awd_meta( $post_id, 'date_start' );
	$b = awd_meta( $post_id, 'date_end' );
	if ( ! $a ) {
		return '';
	}
	$ta = strtotime( $a );
	$tb = $b ? strtotime( $b ) : 0;
	if ( ! $tb || $tb <= $ta ) {
		return date_i18n( 'j M Y', $ta );
	}
	if ( gmdate( 'Y-m', $ta ) === gmdate( 'Y-m', $tb ) ) {
		return date_i18n( 'j', $ta ) . '–' . date_i18n( 'j M Y', $tb );
	}
	return date_i18n( 'j M', $ta ) . ' – ' . date_i18n( 'j M Y', $tb );
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
		'home'  => '<path d="M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
		'sun'   => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'door'  => '<path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17M3 21h18"/><circle cx="15" cy="12" r="1"/>',
		'bag'   => '<path d="M5 8h14l-1 13H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'book'  => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21a2 2 0 0 1 2-2h13v2"/>',
		'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	);
	return isset( $i[ $name ] ) ? '<svg class="awd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $i[ $name ] . '</svg>' : '';
}

/**
 * Point the theme's homepage/practitioner links at the directory and "My Practice".
 */
add_filter( 'theme_mod_awenly_practitioners_url', function ( $v ) {
	return $v ? $v : awd_directory_url();
} );
