<?php
/**
 * Awenly Circle homepage (v3 — light, lavender + lime).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hero_img  = awenly_opt( 'hero_image' );
if ( ! $hero_img ) {
	$hero_img = AWENLY_CHILD_URI . '/assets/img/awenly-hero.jpg';
}
$img_uri   = AWENLY_CHILD_URI . '/assets/img/';
$join_url  = awenly_opt( 'join_url' );
$dir_url   = awenly_opt( 'practitioners_url' );
$events    = awenly_upcoming_events( 3 );
$next      = $events ? $events[0] : null;
$next_ts   = $next ? awenly_event_start_ts( $next->ID ) : 0;

$features = array(
	array( 'connect', __( 'Join the Community', 'awenly-child' ), __( 'Connect, share and grow with like-minded people.', 'awenly-child' ), $join_url ),
	array( 'learn', __( 'Learn & Grow', 'awenly-child' ), __( 'Courses, workshops and practical tools for real change.', 'awenly-child' ), awenly_opt( 'courses_url' ) ),
	array( 'heal', __( 'Find a Practitioner', 'awenly-child' ), __( 'Discover therapists, coaches and healers for 1:1 sessions.', 'awenly-child' ), $dir_url ),
	array( 'grow', __( 'Live Events', 'awenly-child' ), __( 'Retreats, circles and online gatherings with experienced guides.', 'awenly-child' ), awenly_opt( 'events_url' ) ),
);
$chips = array(
	'Therapy'        => 'therapy',
	'Energy Healing' => 'reiki',
	'Coaching'       => 'coaching',
	'Wellbeing'      => 'wellbeing',
	'Meditation'     => 'meditation',
	'Sound Healing'  => 'sound',
);
?>
<div class="awenly-front">

	<!-- ============ HERO ============ -->
	<section class="aw3-hero">
		<div class="aw3-hero__media aw-enter aw-enter--2<?php echo $hero_img ? ' has-image' : ''; ?>"
			<?php if ( $hero_img ) : ?>style="--awenly-hero-image:url('<?php echo esc_url( $hero_img ); ?>')"<?php endif; ?>>
			<?php if ( ! $hero_img ) {
				echo awenly_stone_circle_art(); // phpcs:ignore
			} ?>
		</div>
		<div class="awenly-wrap aw3-hero__grid">
			<div class="aw3-hero__copy aw-enter">
				<span class="aw3-kicker"><?php esc_html_e( 'A community for', 'awenly-child' ); ?></span>
				<h1 class="aw3-hero__title"><?php esc_html_e( 'Personal Growth, Healing & Conscious Living', 'awenly-child' ); ?></h1>
				<p class="aw3-hero__text"><?php esc_html_e( 'Connect with like-minded people, learn from experienced practitioners, join live events and explore tools for your wellbeing and spiritual journey.', 'awenly-child' ); ?></p>
				<ul class="aw3-checks">
					<li><?php esc_html_e( 'Live Workshops', 'awenly-child' ); ?></li>
					<li><?php esc_html_e( 'Online Courses', 'awenly-child' ); ?></li>
					<li><?php esc_html_e( 'Practitioner Directory', 'awenly-child' ); ?></li>
					<li><?php esc_html_e( 'Supportive Community', 'awenly-child' ); ?></li>
				</ul>
				<form class="aw3-join" action="<?php echo esc_url( $join_url ); ?>" method="get">
					<label class="aw3-field"><?php echo awenly_icon( 'user' ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Your name', 'awenly-child' ); ?></span><input type="text" name="aw_name" placeholder="<?php esc_attr_e( 'Your Name', 'awenly-child' ); ?>" autocomplete="name"></label>
					<label class="aw3-field"><?php echo awenly_icon( 'mail' ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Email address', 'awenly-child' ); ?></span><input type="email" name="aw_email" placeholder="<?php esc_attr_e( 'Email Address', 'awenly-child' ); ?>" autocomplete="email"></label>
					<button type="submit" class="aw3-btn aw3-btn--lime"><?php esc_html_e( 'Join Now – It’s Free', 'awenly-child' ); ?> <?php echo awenly_icon( 'arrow' ); // phpcs:ignore ?></button>
				</form>
			</div>
		</div>
	</section>

	<!-- ============ NEXT LIVE ============ -->
	<section class="awenly-wrap aw3-next aw-reveal">
		<span class="aw3-next__badge"><?php echo awenly_icon( 'video' ); // phpcs:ignore ?><?php esc_html_e( 'Next Live', 'awenly-child' ); ?></span>
		<div class="aw3-next__thumb"><?php echo $next && has_post_thumbnail( $next ) ? get_the_post_thumbnail( $next, 'thumbnail', array( 'alt' => '' ) ) : '<img src="' . esc_url( $img_uri . 'awenly-live.jpg' ) . '" alt="" loading="lazy">'; // phpcs:ignore ?></div>
		<strong class="aw3-next__title"><?php echo $next ? esc_html( get_the_title( $next ) ) : esc_html__( 'Weekly Community Circle', 'awenly-child' ); ?></strong>
		<span class="aw3-next__meta"><?php echo awenly_icon( 'calendar' ); // phpcs:ignore ?>
			<?php echo $next_ts ? esc_html( wp_date( 'D, j M · H:i', $next_ts ) ) : esc_html__( 'Dates announced soon', 'awenly-child' ); ?></span>
		<span class="aw3-next__meta"><?php echo awenly_icon( 'pin' ); // phpcs:ignore ?>
			<?php $loc = $next ? awenly_event_location( $next->ID ) : ''; echo esc_html( $loc ? $loc : __( 'Live online & in person', 'awenly-child' ) ); ?></span>
		<a class="aw3-btn aw3-btn--lime aw3-btn--sm" href="<?php echo esc_url( $next ? awenly_event_link( $next->ID ) : $join_url ); ?>"><?php echo $next ? esc_html__( 'Save My Spot', 'awenly-child' ) : esc_html__( 'Get Notified', 'awenly-child' ); ?></a>
	</section>

	<!-- ============ FEATURES ============ -->
	<section class="awenly-wrap aw3-features">
		<?php foreach ( $features as $n => $f ) : ?>
			<a class="aw3-feature aw-reveal aw-delay-<?php echo (int) min( 2, $n ); ?>" href="<?php echo esc_url( $f[3] ); ?>">
				<span class="aw3-feature__icon"><?php echo awenly_icon( $f[0] ); // phpcs:ignore ?></span>
				<span><strong><?php echo esc_html( $f[1] ); ?></strong><small><?php echo esc_html( $f[2] ); ?></small></span>
			</a>
		<?php endforeach; ?>
	</section>

	<!-- ============ DIRECTORY + STEPS ============ -->
	<section class="awenly-wrap aw3-split">
		<div class="aw3-directory aw-reveal">
			<div class="aw3-directory__media"><img src="<?php echo esc_url( $img_uri . 'awenly-directory.jpg' ); ?>" alt="" loading="lazy"></div>
			<div class="aw3-directory__body">
				<span class="aw3-kicker"><?php esc_html_e( 'Find your healer, coach or guide', 'awenly-child' ); ?></span>
				<h2><?php esc_html_e( 'A Directory of Trusted Practitioners', 'awenly-child' ); ?></h2>
				<p><?php esc_html_e( 'Explore a growing network of therapists, coaches and holistic practitioners offering sessions online and in person.', 'awenly-child' ); ?></p>
				<div class="aw3-chips">
					<?php foreach ( $chips as $label => $q ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 's_practitioner', $q, $dir_url ) ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
					<a href="<?php echo esc_url( $dir_url ); ?>" class="aw3-chips__more"><?php esc_html_e( 'and more…', 'awenly-child' ); ?></a>
				</div>
				<a class="aw3-btn aw3-btn--lime" href="<?php echo esc_url( $dir_url ); ?>"><?php esc_html_e( 'Browse Practitioners', 'awenly-child' ); ?> <?php echo awenly_icon( 'arrow' ); // phpcs:ignore ?></a>
			</div>
		</div>
		<aside class="aw3-steps aw-reveal aw-delay-1">
			<h2><?php esc_html_e( 'Three Steps In', 'awenly-child' ); ?></h2>
			<ol>
				<li><b>1</b><span><strong><?php esc_html_e( 'Create Your Free Account', 'awenly-child' ); ?></strong><?php esc_html_e( 'It only takes a minute.', 'awenly-child' ); ?></span></li>
				<li><b>2</b><span><strong><?php esc_html_e( 'Explore & Connect', 'awenly-child' ); ?></strong><?php esc_html_e( 'Find people, events and practitioners that resonate with you.', 'awenly-child' ); ?></span></li>
				<li><b>3</b><span><strong><?php esc_html_e( 'Grow at Your Own Pace', 'awenly-child' ); ?></strong><?php esc_html_e( 'Join workshops, courses and community circles that support your journey.', 'awenly-child' ); ?></span></li>
			</ol>
			<?php echo awenly_logo_mark( 150, 'awenly-mark aw3-steps__mark' ); // phpcs:ignore ?>
		</aside>
	</section>

	<!-- ============ EVENTS ============ -->
	<section class="awenly-wrap aw3-events">
		<header class="aw3-head aw-reveal">
			<div>
				<span class="aw3-kicker"><?php esc_html_e( 'Gather with us', 'awenly-child' ); ?></span>
				<h2><?php esc_html_e( 'Upcoming Events', 'awenly-child' ); ?></h2>
			</div>
			<a class="aw3-link" href="<?php echo esc_url( awenly_opt( 'events_url' ) ); ?>"><?php esc_html_e( 'View all events', 'awenly-child' ); ?> <?php echo awenly_icon( 'arrow' ); // phpcs:ignore ?></a>
		</header>
		<div class="aw3-events__grid">
			<?php if ( $events ) :
				foreach ( $events as $n => $ev ) :
					$ts   = awenly_event_start_ts( $ev->ID );
					$loc  = awenly_event_location( $ev->ID );
					$link = awenly_event_link( $ev->ID ); ?>
					<article class="aw3-event aw-reveal aw-delay-<?php echo (int) $n; ?>">
						<div class="aw3-event__media awenly-art-f<?php echo (int) ( $n % 3 ) + 1; ?>">
							<?php echo has_post_thumbnail( $ev ) ? get_the_post_thumbnail( $ev, 'awenly-card', array( 'alt' => '' ) ) : '<img src="' . esc_url( $img_uri . 'awenly-event-' . ( ( $n % 3 ) + 1 ) . '.jpg' ) . '" alt="" loading="lazy">'; // phpcs:ignore ?>
							<?php if ( $ts ) : ?><span class="aw3-event__date"><small><?php echo esc_html( wp_date( 'D', $ts ) ); ?></small><b><?php echo esc_html( wp_date( 'd', $ts ) ); ?></b><small><?php echo esc_html( wp_date( 'M', $ts ) ); ?></small></span><?php endif; ?>
						</div>
						<div class="aw3-event__body">
							<h3><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $ev ) ); ?></a></h3>
							<?php if ( $loc ) : ?><p><?php echo awenly_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( $loc ); ?></p><?php endif; ?>
							<?php if ( $ts ) : ?><p><?php echo awenly_icon( 'clock' ); // phpcs:ignore ?><?php $end = awenly_event_end( $ev->ID ); echo esc_html( wp_date( 'H:i', $ts ) . ( $end ? ' – ' . $end : '' ) ); ?></p><?php endif; ?>
						</div>
					</article>
				<?php endforeach;
			else :
				foreach ( array( array( 'Healing Circles', 'Small gatherings to share, listen and hold space for each other.' ), array( 'Workshops', 'Hands-on sessions led by practitioners — in person and online.' ), array( 'Retreats', 'Days away to slow down, reconnect with nature and reset.' ) ) as $n => $f ) : ?>
					<article class="aw3-event aw-reveal aw-delay-<?php echo (int) $n; ?>">
						<div class="aw3-event__media awenly-art-f<?php echo (int) $n + 1; ?>"><img src="<?php echo esc_url( $img_uri . 'awenly-event-' . ( $n + 1 ) . '.jpg' ); ?>" alt="" loading="lazy"><span class="aw3-event__soon"><?php esc_html_e( 'Dates coming soon', 'awenly-child' ); ?></span></div>
						<div class="aw3-event__body"><h3><a href="<?php echo esc_url( $join_url ); ?>"><?php echo esc_html( $f[0] ); ?></a></h3><p class="aw3-event__desc"><?php echo esc_html( $f[1] ); ?></p></div>
					</article>
				<?php endforeach;
			endif; ?>
		</div>
	</section>

	<!-- ============ CTA ============ -->
	<section class="awenly-wrap aw3-cta aw-reveal">
		<div>
			<h2><?php esc_html_e( 'There is a place for you in the circle.', 'awenly-child' ); ?></h2>
			<p><?php esc_html_e( 'Whether you are just starting out or have walked this path for years — come as you are.', 'awenly-child' ); ?></p>
		</div>
		<a class="aw3-btn aw3-btn--lime" href="<?php echo esc_url( $join_url ); ?>"><?php esc_html_e( 'Join Awenly Circle', 'awenly-child' ); ?> <?php echo awenly_icon( 'arrow' ); // phpcs:ignore ?></a>
	</section>

	<?php
	while ( have_posts() ) :
		the_post();
		$content = trim( get_the_content() );
		if ( $content && false === strpos( $content, '[awenly_home' ) ) :
			?>
			<section class="awenly-wrap entry-content aw3-page-content"><?php the_content(); ?></section>
			<?php
		endif;
	endwhile;
	?>
</div>
<?php
get_footer();
