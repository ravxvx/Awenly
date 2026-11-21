<?php
/**
 * Practitioner directory (archive) template.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$filters = awd_current_filters();
$paged   = max( 1, (int) get_query_var( 'paged' ) );
$q       = awd_query( $filters, $paged );
$terms   = get_terms( array( 'taxonomy' => AWD_TAX, 'hide_empty' => false ) );
$modes   = awd_fields()['mode']['options'];
get_header();
?>
<div class="awd-directory">
	<section class="awd-hero">
		<div class="awd-container">
			<span class="awd-kicker"><?php esc_html_e( 'Find your healer, coach or guide', 'awenly-directory' ); ?></span>
			<h1><?php esc_html_e( 'Practitioner Directory', 'awenly-directory' ); ?></h1>
			<p><?php esc_html_e( 'Therapists, coaches and holistic practitioners from the Awenly Circle community — online and in person.', 'awenly-directory' ); ?></p>
			<form class="awd-filters" method="get" action="<?php echo esc_url( awd_directory_url() ); ?>">
				<input type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'Name or keyword', 'awenly-directory' ); ?>" aria-label="<?php esc_attr_e( 'Name or keyword', 'awenly-directory' ); ?>">
				<select name="specialty" aria-label="<?php esc_attr_e( 'Specialisation', 'awenly-directory' ); ?>">
					<option value=""><?php esc_html_e( 'All specialisations', 'awenly-directory' ); ?></option>
					<?php foreach ( (array) $terms as $t ) : ?>
						<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $filters['specialty'], $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="location" value="<?php echo esc_attr( $filters['location'] ); ?>" placeholder="<?php esc_attr_e( 'City', 'awenly-directory' ); ?>" aria-label="<?php esc_attr_e( 'City', 'awenly-directory' ); ?>">
				<select name="mode" aria-label="<?php esc_attr_e( 'Session format', 'awenly-directory' ); ?>">
					<option value=""><?php esc_html_e( 'Any format', 'awenly-directory' ); ?></option>
					<?php foreach ( $modes as $mv => $ml ) : ?>
						<option value="<?php echo esc_attr( $mv ); ?>" <?php selected( $filters['mode'], $mv ); ?>><?php echo esc_html( $ml ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="awd-btn"><?php esc_html_e( 'Search', 'awenly-directory' ); ?></button>
				<div class="awd-filters__row">
					<label class="awd-check"><input type="checkbox" name="verified" value="1" <?php checked( $filters['verified'] ); ?>> <?php esc_html_e( 'Verified only', 'awenly-directory' ); ?></label>
					<label class="awd-sort"><?php esc_html_e( 'Sort:', 'awenly-directory' ); ?>
						<select name="sort" onchange="this.form.submit()">
							<option value=""><?php esc_html_e( 'Recommended', 'awenly-directory' ); ?></option>
							<option value="rating" <?php selected( $filters['sort'], 'rating' ); ?>><?php esc_html_e( 'Top rated', 'awenly-directory' ); ?></option>
							<option value="new" <?php selected( $filters['sort'], 'new' ); ?>><?php esc_html_e( 'Newest', 'awenly-directory' ); ?></option>
						</select></label>
				</div>
			</form>
			<div class="awd-quick">
				<a href="<?php echo esc_url( awd_directory_url() ); ?>" class="<?php echo $filters['specialty'] ? '' : 'is-active'; ?>"><?php esc_html_e( 'All', 'awenly-directory' ); ?></a>
				<?php foreach ( (array) $terms as $t ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'specialty', $t->slug, awd_directory_url() ) ); ?>" class="<?php echo $filters['specialty'] === $t->slug ? 'is-active' : ''; ?>"><?php echo esc_html( $t->name ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="awd-container awd-results">
		<?php
		if ( $q->have_posts() ) {
			$all_ids = awd_query( $filters, 1, array( 'posts_per_page' => 300, 'fields' => 'ids', 'no_found_rows' => true ) )->posts;
			awd_render_map( awd_map_points( $all_ids ), 'awd-map--directory' );
		}
		?>
		<?php if ( $q->have_posts() ) : ?>
			<p class="awd-count"><?php echo esc_html( sprintf( _n( '%d practitioner', '%d practitioners', $q->found_posts, 'awenly-directory' ), $q->found_posts ) ); ?></p>
			<div class="awd-grid-cards">
				<?php while ( $q->have_posts() ) :
					$q->the_post();
					awd_render_card( get_post() );
				endwhile; ?>
			</div>
			<?php
			echo '<nav class="awd-pagination">' . paginate_links( array( // phpcs:ignore
				'total'   => $q->max_num_pages,
				'current' => $paged,
			) ) . '</nav>';
			wp_reset_postdata();
			?>
		<?php else : ?>
			<div class="awd-empty">
				<h2><?php esc_html_e( 'No practitioners found yet', 'awenly-directory' ); ?></h2>
				<p><?php echo ( $filters['q'] || $filters['specialty'] || $filters['mode'] || $filters['location'] || $filters['verified'] ) ? esc_html__( 'Try a different filter — or be the first to list your practice.', 'awenly-directory' ) : esc_html__( 'Our directory is just opening. Be one of the first practitioners in the circle.', 'awenly-directory' ); ?></p>
			</div>
		<?php endif; ?>

		<aside class="awd-cta">
			<div>
				<h2><?php esc_html_e( 'Are you a therapist, coach or healer?', 'awenly-directory' ); ?></h2>
				<p><?php esc_html_e( 'Create your free listing in a few minutes and connect with people looking for exactly what you offer.', 'awenly-directory' ); ?></p>
			</div>
			<a class="awd-btn" href="<?php echo esc_url( awd_practice_url() ); ?>"><?php esc_html_e( 'List your practice — free', 'awenly-directory' ); ?></a>
		</aside>
	</section>
</div>
<?php
get_footer();
