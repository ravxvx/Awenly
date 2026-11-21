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
			<span class="awd-kicker"><?php esc_html_e( 'Practitioners · centres · retreats · venues · shops · courses', 'awenly-directory' ); ?></span>
			<h1><?php esc_html_e( 'Awenly Directory', 'awenly-directory' ); ?></h1>
			<p><?php esc_html_e( 'Therapists, coaches, healers, studios, retreats and more from the Awenly Circle community — online and in person.', 'awenly-directory' ); ?></p>
			<nav class="awd-typetabs" aria-label="<?php esc_attr_e( 'Listing type', 'awenly-directory' ); ?>">
				<a href="<?php echo esc_url( remove_query_arg( array( 'type', 'paged' ) ) ); ?>" class="<?php echo $filters['type'] ? '' : 'is-active'; ?>"><?php esc_html_e( 'Everything', 'awenly-directory' ); ?></a>
				<?php foreach ( awd_types() as $tk => $tv ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'type', $tk, remove_query_arg( 'paged' ) ) ); ?>" class="<?php echo $filters['type'] === $tk ? 'is-active' : ''; ?>"><?php echo awd_icon( $tv['icon'] ); // phpcs:ignore ?><?php echo esc_html( $tv['plural'] ); ?></a>
				<?php endforeach; ?>
			</nav>
			<form class="awd-filters" method="get" action="<?php echo esc_url( awd_directory_url() ); ?>">
				<input type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'Name or keyword', 'awenly-directory' ); ?>" aria-label="<?php esc_attr_e( 'Name or keyword', 'awenly-directory' ); ?>">
				<select name="specialty" aria-label="<?php esc_attr_e( 'Category', 'awenly-directory' ); ?>">
					<option value=""><?php esc_html_e( 'All categories', 'awenly-directory' ); ?></option>
					<?php foreach ( (array) $terms as $t ) : ?>
						<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $filters['specialty'], $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" name="location" value="<?php echo esc_attr( $filters['location'] ); ?>" placeholder="<?php esc_attr_e( 'City', 'awenly-directory' ); ?>" aria-label="<?php esc_attr_e( 'City', 'awenly-directory' ); ?>">
				<select name="mode" aria-label="<?php esc_attr_e( 'Format', 'awenly-directory' ); ?>">
					<option value=""><?php esc_html_e( 'Any format', 'awenly-directory' ); ?></option>
					<?php foreach ( $modes as $mv => $ml ) : ?>
						<option value="<?php echo esc_attr( $mv ); ?>" <?php selected( $filters['mode'], $mv ); ?>><?php echo esc_html( $ml ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( $filters['type'] ) : ?><input type="hidden" name="type" value="<?php echo esc_attr( $filters['type'] ); ?>"><?php endif; ?>
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
				<?php $ti = 0; foreach ( (array) $terms as $t ) : $ti++; ?>
					<a href="<?php echo esc_url( add_query_arg( 'specialty', $t->slug, awd_directory_url() ) ); ?>" class="<?php echo $filters['specialty'] === $t->slug ? 'is-active' : ( $ti > 9 ? 'is-extra' : '' ); ?>"><?php echo esc_html( $t->name ); ?></a>
				<?php endforeach; ?>
				<?php if ( count( (array) $terms ) > 9 ) : ?><button type="button" class="awd-quick__more" onclick="this.parentNode.classList.toggle('is-open')"><span><?php echo esc_html( sprintf( __( '+ %d more', 'awenly-directory' ), count( (array) $terms ) - 9 ) ); ?></span><span><?php esc_html_e( 'Show less', 'awenly-directory' ); ?></span></button><?php endif; ?>
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
			<p class="awd-count"><?php echo esc_html( sprintf( _n( '%d listing', '%d listings', $q->found_posts, 'awenly-directory' ), $q->found_posts ) ); ?></p>
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
				<h2><?php esc_html_e( 'Nothing found yet', 'awenly-directory' ); ?></h2>
				<p><?php echo ( $filters['q'] || $filters['specialty'] || $filters['mode'] || $filters['location'] || $filters['verified'] || $filters['type'] ) ? esc_html__( 'Try a different filter — or be the first to add a listing here.', 'awenly-directory' ) : esc_html__( 'Our directory is just opening. Be one of the first in the circle.', 'awenly-directory' ); ?></p>
			</div>
		<?php endif; ?>

		<aside class="awd-cta">
			<div>
				<h2><?php esc_html_e( 'Run a practice, studio, retreat or shop?', 'awenly-directory' ); ?></h2>
				<p><?php esc_html_e( 'Create a free listing in a few minutes and connect with people looking for exactly what you offer.', 'awenly-directory' ); ?></p>
			</div>
			<a class="awd-btn" href="<?php echo esc_url( awd_practice_url() ); ?>"><?php esc_html_e( 'Add your listing — free', 'awenly-directory' ); ?></a>
		</aside>
	</section>
</div>
<?php
get_footer();
