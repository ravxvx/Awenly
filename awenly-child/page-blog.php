<?php
/**
 * /blog/ — article list in the Awenly style (replaces the old Elementor layout whose post list was empty).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$awb_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$awb_cat   = isset( $_GET['articles'] ) ? sanitize_title( wp_unslash( $_GET['articles'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$awb_args  = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 12,
	'paged'               => $awb_paged,
	'ignore_sticky_posts' => true,
);
if ( $awb_cat ) {
	$awb_args['category_name'] = $awb_cat;
}
$awb_q    = new WP_Query( $awb_args );
$awb_cats = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'number' => 10 ) );
$awb_base = get_permalink();
?>
<div class="awb">
	<section class="awb-hero">
		<div class="awenly-wrap">
			<span class="awb-kicker"><?php esc_html_e( 'Blog', 'awenly-child' ); ?></span>
			<h1><?php esc_html_e( 'Articles on healing, wellbeing & spirituality', 'awenly-child' ); ?></h1>
			<p><?php esc_html_e( 'Practical ideas, gentle practices and stories from practitioners and members of the Awenly Circle community.', 'awenly-child' ); ?></p>
			<nav class="awb-topics" aria-label="<?php esc_attr_e( 'Topics', 'awenly-child' ); ?>">
				<a href="<?php echo esc_url( $awb_base ); ?>"<?php echo $awb_cat ? '' : ' class="is-on"'; ?>><?php esc_html_e( 'All', 'awenly-child' ); ?></a>
				<?php foreach ( $awb_cats as $c ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'articles', $c->slug, $awb_base ) ); ?>"<?php echo $awb_cat === $c->slug ? ' class="is-on"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
	</section>

	<section class="awenly-wrap awb-body">
		<?php if ( $awb_q->have_posts() ) : ?>
			<div class="awb-grid">
				<?php while ( $awb_q->have_posts() ) : $awb_q->the_post();
					$cat = get_the_category();
					?>
					<article class="awb-card">
						<a class="awb-card__img" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => '' ) );
							} else {
								echo '<span class="awb-card__ph"></span>';
							} ?>
						</a>
						<div class="awb-card__body">
							<?php if ( $cat ) : ?><span class="awb-card__cat"><?php echo esc_html( $cat[0]->name ); ?></span><?php endif; ?>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
							<span class="awb-card__meta"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ) ); ?> <?php esc_html_e( 'min read', 'awenly-child' ); ?></span>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php
			$links = paginate_links( array(
				'base'      => trailingslashit( $awb_base ) . 'page/%#%/' . ( $awb_cat ? '?articles=' . rawurlencode( $awb_cat ) : '' ),
				'format'    => '',
				'current'   => $awb_paged,
				'total'     => $awb_q->max_num_pages,
				'prev_text' => '←',
				'next_text' => '→',
			) );
			if ( $links ) {
				echo '<nav class="awb-pages">' . $links . '</nav>'; // phpcs:ignore
			}
			wp_reset_postdata();
			?>
		<?php else : ?>
			<p class="awb-empty"><?php esc_html_e( 'No articles here yet.', 'awenly-child' ); ?></p>
		<?php endif; ?>
	</section>
</div>
<?php
get_footer();
