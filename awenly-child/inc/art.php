<?php
/**
 * Hand-built SVG artwork for the homepage (no external images needed).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twilight mountain landscape with sun, mist, stars and a lavender field.
 */
function awenly_hero_art() {
	mt_srand( 11 );

	$stars = '';
	for ( $i = 0; $i < 70; $i++ ) {
		$x = mt_rand( 0, 1600 );
		$y = mt_rand( 0, 300 );
		$r = mt_rand( 5, 16 ) / 10;
		$d = mt_rand( 0, 50 ) / 10;
		$stars .= '<circle class="aw-star" cx="' . $x . '" cy="' . $y . '" r="' . $r . '" style="animation-delay:' . $d . 's"/>';
	}

	// Lavender field: rows of small flower spikes, denser and bigger towards the viewer.
	$flowers = '';
	$colors  = array( '#b16cf0', '#c98bff', '#9b4fe0', '#d9a6ff', '#8a3fd1', '#e6b8ff' );
	for ( $row = 0; $row < 16; $row++ ) {
		$y     = 600 + $row * 7;
		$scale = 0.5 + $row * 0.11;
		$count = 40 + $row * 5;
		for ( $i = 0; $i < $count; $i++ ) {
			$x  = mt_rand( -20, 1620 );
			$yy = $y + mt_rand( -6, 6 );
			$h  = ( 9 + mt_rand( 0, 9 ) ) * $scale;
			$w  = 1.3 * $scale;
			$c  = $colors[ mt_rand( 0, count( $colors ) - 1 ) ];
			$flowers .= '<ellipse cx="' . $x . '" cy="' . round( $yy - $h / 2, 1 ) . '" rx="' . round( $w, 1 ) . '" ry="' . round( $h / 2, 1 ) . '" fill="' . $c . '"/>';
		}
	}

	$fireflies = '';
	for ( $i = 0; $i < 22; $i++ ) {
		$x = mt_rand( 80, 1520 );
		$y = mt_rand( 380, 640 );
		$d = mt_rand( 0, 80 ) / 10;
		$t = mt_rand( 60, 120 ) / 10;
		$fireflies .= '<circle class="aw-firefly" cx="' . $x . '" cy="' . $y . '" r="2.4" style="animation-delay:-' . $d . 's;animation-duration:' . $t . 's"/>';
	}

	return '<svg class="awenly-hero__art" viewBox="0 0 1600 720" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="aw-sky" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" stop-color="#2a0f5c"/>
			<stop offset=".35" stop-color="#5b2a9e"/>
			<stop offset=".62" stop-color="#b65fa8"/>
			<stop offset=".8" stop-color="#f19a7b"/>
			<stop offset="1" stop-color="#ffcf8a"/>
		</linearGradient>
		<radialGradient id="aw-sun" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(1290 330) scale(420)">
			<stop offset="0" stop-color="#fffbe6"/>
			<stop offset=".08" stop-color="#fff0b8"/>
			<stop offset=".22" stop-color="#ffc98a" stop-opacity=".85"/>
			<stop offset=".55" stop-color="#ff9a8a" stop-opacity=".25"/>
			<stop offset="1" stop-color="#ff9a8a" stop-opacity="0"/>
		</radialGradient>
		<linearGradient id="aw-m1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b77bc9"/><stop offset="1" stop-color="#d79bb0"/></linearGradient>
		<linearGradient id="aw-m2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8a52b8"/><stop offset="1" stop-color="#b0739f"/></linearGradient>
		<linearGradient id="aw-m3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5e2f96"/><stop offset="1" stop-color="#7c4492"/></linearGradient>
		<linearGradient id="aw-m4" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3b1a6e"/><stop offset="1" stop-color="#2a1250"/></linearGradient>
		<linearGradient id="aw-mist" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd2c2" stop-opacity="0"/><stop offset=".5" stop-color="#ffd2c2" stop-opacity=".45"/><stop offset="1" stop-color="#ffd2c2" stop-opacity="0"/></linearGradient>
		<linearGradient id="aw-field" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3a1868"/><stop offset="1" stop-color="#1f0b3d"/></linearGradient>
	</defs>
	<rect width="1600" height="720" fill="url(#aw-sky)"/>
	<g class="aw-stars">' . $stars . '</g>
	<circle cx="1290" cy="330" r="420" fill="url(#aw-sun)" class="aw-sun-glow"/>
	<circle cx="1290" cy="330" r="46" fill="#fff6d6" class="aw-sun"/>
	<g class="aw-rays" stroke="#fff3cf" stroke-opacity=".18" stroke-width="2">
		<path d="M1290 330 L1600 120"/><path d="M1290 330 L1600 260"/><path d="M1290 330 L1600 420"/><path d="M1290 330 L1050 0"/><path d="M1290 330 L1450 0"/><path d="M1290 330 L1250 0"/>
	</g>
	<path class="aw-layer aw-l1" d="M0 420 L120 360 L230 395 L360 300 L470 370 L600 320 L720 380 L860 290 L990 360 L1120 330 L1240 390 L1380 320 L1500 370 L1600 340 L1600 720 L0 720Z" fill="url(#aw-m1)" opacity=".75"/>
	<rect y="370" width="1600" height="80" fill="url(#aw-mist)" class="aw-mist"/>
	<path class="aw-layer aw-l2" d="M0 470 L140 400 L260 450 L420 360 L560 450 L700 410 L820 470 L980 380 L1120 460 L1260 420 L1400 480 L1520 430 L1600 460 L1600 720 L0 720Z" fill="url(#aw-m2)"/>
	<rect y="440" width="1600" height="90" fill="url(#aw-mist)" opacity=".7" class="aw-mist aw-mist--2"/>
	<path class="aw-layer aw-l3" d="M0 540 Q160 450 320 520 T640 500 Q760 440 900 520 T1200 510 Q1340 450 1460 520 T1600 520 L1600 720 L0 720Z" fill="url(#aw-m3)"/>
	<path class="aw-layer aw-l4" d="M0 600 Q200 540 420 590 T860 585 Q1060 545 1280 595 T1600 590 L1600 720 L0 720Z" fill="url(#aw-field)"/>
	<g class="aw-flowers">' . $flowers . '</g>
	<g class="aw-fireflies">' . $fireflies . '</g>
</svg>';
}

/**
 * Night sky with stars for the statement band.
 */
function awenly_starfield() {
	mt_srand( 23 );
	$s = '';
	for ( $i = 0; $i < 90; $i++ ) {
		$s .= '<circle class="aw-star" cx="' . mt_rand( 0, 1600 ) . '" cy="' . mt_rand( 0, 500 ) . '" r="' . ( mt_rand( 4, 15 ) / 10 ) . '" style="animation-delay:' . ( mt_rand( 0, 50 ) / 10 ) . 's"/>';
	}
	return '<svg class="awenly-starfield" viewBox="0 0 1600 500" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false"><g fill="#fff">' . $s . '</g></svg>';
}

/**
 * Icons for the "paths" tiles.
 */
function awenly_path_icon( $name ) {
	$p = array(
		'meditation' => '<circle cx="12" cy="5" r="2.2"/><path d="M12 8v5M7 12l5 1 5-1M5 19c2-2 4-3 7-3s5 1 7 3M8 16l-3 3M16 16l3 3"/>',
		'energy'     => '<path d="M12 3c3 4 5 6.5 5 9.5A5 5 0 0 1 7 12.5C7 9.5 9 7 12 3z"/><path d="M12 10c1.2 1.6 2 2.6 2 3.8a2 2 0 0 1-4 0c0-1.2.8-2.2 2-3.8z"/>',
		'coaching'   => '<path d="M4 20l5-5M9 15l2.5-6L20 4l-5 8.5L9 15z"/><circle cx="15" cy="9" r="1.2"/>',
		'sound'      => '<path d="M4 12c2.5 4 13.5 4 16 0"/><path d="M5 12h14"/><path d="M9 8c0-1.5 1.3-3 3-3s3 1.5 3 3"/><path d="M2 9c.8-1 .8-2 0-3M22 9c-.8-1-.8-2 0-3"/>',
		'shamanic'   => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M12 4v5M12 15v5M4 12h5M15 12h5"/>',
		'movement'   => '<circle cx="13" cy="4.5" r="2"/><path d="M6 21l4-6 3 2 1-6 4 3M9 9l4-1"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $p[ $name ] ?? '' ) . '</svg>';
}

/**
 * Sacred geometry: Flower of Life + Seed rings, thin gold line art.
 */
function awenly_sacred_geometry( $class = 'awenly-geo' ) {
	$c   = '';
	$r   = 60;
	$pts = array( array( 0, 0 ) );
	for ( $ring = 1; $ring <= 2; $ring++ ) {
		for ( $k = 0; $k < 6 * $ring; $k++ ) {
			$side = intdiv( $k, $ring );
			$step = $k % $ring;
			$a1   = deg2rad( 60 * $side - 90 );
			$a2   = deg2rad( 60 * ( $side + 1 ) - 90 );
			$x    = $ring * $r * cos( $a1 ) + $step * $r * ( cos( $a2 ) - cos( $a1 ) );
			$y    = $ring * $r * sin( $a1 ) + $step * $r * ( sin( $a2 ) - sin( $a1 ) );
			$pts[] = array( round( $x, 2 ), round( $y, 2 ) );
		}
	}
	foreach ( $pts as $p ) {
		$c .= '<circle cx="' . $p[0] . '" cy="' . $p[1] . '" r="' . $r . '"/>';
	}
	return '<svg class="' . esc_attr( $class ) . '" viewBox="-320 -320 640 640" aria-hidden="true" focusable="false">'
		. '<g fill="none" stroke="currentColor" stroke-width="0.8">'
		. '<circle r="300" class="g-o1"/><circle r="306" stroke-dasharray="2 8" class="g-o2"/><circle r="182"/>'
		. '<g class="g-flower">' . $c . '</g>'
		. '<g class="g-tri"><path d="M0 -300 L259.8 150 L-259.8 150Z"/><path d="M0 300 L259.8 -150 L-259.8 -150Z"/></g>'
		. '</g></svg>';
}

/**
 * Hero card artwork: dawn sky, glowing Awen, hills, standing-stone circle and heather.
 */
function awenly_stone_circle_art() {
	mt_srand( 5 );
	$heather = '';
	$cols    = array( '#9b4fe0', '#b46cf0', '#c98bff', '#8a3fd1', '#d7a6ff', '#7b34c4' );
	for ( $row = 0; $row < 18; $row++ ) {
		$y = 470 + $row * 9;
		$s = 0.6 + $row * 0.12;
		for ( $i = 0; $i < 34 + $row * 4; $i++ ) {
			$x = mt_rand( -20, 920 );
			$h = ( 8 + mt_rand( 0, 9 ) ) * $s;
			$heather .= '<ellipse cx="' . $x . '" cy="' . round( $y - $h / 2 + mt_rand( -5, 5 ), 1 ) . '" rx="' . round( 1.5 * $s, 1 ) . '" ry="' . round( $h / 2, 1 ) . '" fill="' . $cols[ mt_rand( 0, 5 ) ] . '"/>';
		}
	}
	$stones = array(
		array( 250, 455, 34, 92 ), array( 330, 440, 30, 70 ), array( 405, 432, 26, 58 ),
		array( 600, 434, 26, 60 ), array( 680, 445, 32, 78 ), array( 765, 462, 40, 104 ),
		array( 500, 428, 22, 44 ),
	);
	$st = '';
	foreach ( $stones as $s ) {
		list( $x, $y, $w, $h ) = $s;
		$st .= '<path d="M' . ( $x - $w / 2 ) . ' ' . $y . ' Q' . ( $x - $w / 2 - 3 ) . ' ' . ( $y - $h * .7 ) . ' ' . ( $x - $w / 4 ) . ' ' . ( $y - $h ) . ' Q' . $x . ' ' . ( $y - $h - 8 ) . ' ' . ( $x + $w / 3 ) . ' ' . ( $y - $h + 4 ) . ' Q' . ( $x + $w / 2 + 4 ) . ' ' . ( $y - $h * .5 ) . ' ' . ( $x + $w / 2 ) . ' ' . $y . ' Z" fill="url(#sc-stone)"/>';
		$st .= '<path d="M' . ( $x - $w / 4 ) . ' ' . ( $y - $h + 6 ) . ' Q' . ( $x - $w / 6 ) . ' ' . ( $y - $h / 2 ) . ' ' . ( $x - $w / 3 ) . ' ' . ( $y - 6 ) . '" stroke="#fff" stroke-opacity=".15" stroke-width="3" fill="none"/>';
	}
	$stars = '';
	for ( $i = 0; $i < 30; $i++ ) {
		$stars .= '<circle class="aw-star" cx="' . mt_rand( 0, 900 ) . '" cy="' . mt_rand( 0, 180 ) . '" r="' . ( mt_rand( 5, 13 ) / 10 ) . '" style="animation-delay:' . ( mt_rand( 0, 40 ) / 10 ) . 's"/>';
	}
	return '<svg class="awenly-card-art" viewBox="0 0 900 640" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
	<defs>
		<linearGradient id="sc-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3d1c78"/><stop offset=".38" stop-color="#8b4fc4"/><stop offset=".62" stop-color="#e79bb5"/><stop offset=".8" stop-color="#ffd2a1"/><stop offset="1" stop-color="#fff0cf"/></linearGradient>
		<radialGradient id="sc-glow" cx="500" cy="200" r="300" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#fff9e6"/><stop offset=".18" stop-color="#ffe9b8" stop-opacity=".9"/><stop offset=".5" stop-color="#ffb8a8" stop-opacity=".3"/><stop offset="1" stop-color="#ffb8a8" stop-opacity="0"/></radialGradient>
		<linearGradient id="sc-h1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#a77bc9"/><stop offset="1" stop-color="#c79ab8"/></linearGradient>
		<linearGradient id="sc-h2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6f8a5a"/><stop offset="1" stop-color="#4f6b44"/></linearGradient>
		<linearGradient id="sc-h3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5b3f6e"/><stop offset="1" stop-color="#3a2552"/></linearGradient>
		<linearGradient id="sc-stone" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9a91a8"/><stop offset=".5" stop-color="#6b6378"/><stop offset="1" stop-color="#3f3850"/></linearGradient>
		<linearGradient id="sc-mist" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#ffe6f0" stop-opacity=".55"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
	</defs>
	<rect width="900" height="640" fill="url(#sc-sky)"/>
	<g>' . $stars . '</g>
	<circle cx="500" cy="200" r="300" fill="url(#sc-glow)" class="aw-sun-glow"/>
	<g class="sc-rays" stroke="#fff6dc" stroke-opacity=".22" stroke-width="2"><path d="M500 200 L900 40"/><path d="M500 200 L900 210"/><path d="M500 200 L120 0"/><path d="M500 200 L20 170"/><path d="M500 200 L700 0"/><path d="M500 200 L320 0"/></g>
	<g class="sc-awen" transform="translate(500 200)" fill="#fff" stroke="#fff">
		<circle r="70" fill="none" stroke-width="5" stroke-opacity=".95"/>
		<circle cx="-22" cy="-34" r="7" stroke="none"/><circle cx="0" cy="-40" r="7" stroke="none"/><circle cx="22" cy="-34" r="7" stroke="none"/>
		<path d="M-25 -18 L-40 32 L-31 35 L-18 -16 Z" stroke="none"/><path d="M-6 -21 L-7 46 L7 46 L6 -21 Z" stroke="none" fill="#fff4d0"/><path d="M25 -18 L40 32 L31 35 L18 -16 Z" stroke="none"/>
	</g>
	<path d="M0 360 L90 320 L190 345 L300 290 L420 340 L540 300 L660 335 L790 285 L900 320 L900 640 L0 640Z" fill="url(#sc-h1)" opacity=".8"/>
	<rect y="320" width="900" height="70" fill="url(#sc-mist)" class="aw-mist"/>
	<path d="M0 420 Q180 360 360 405 T720 395 Q820 380 900 400 L900 640 L0 640Z" fill="url(#sc-h2)"/>
	<path d="M0 470 Q220 430 450 455 T900 450 L900 640 L0 640Z" fill="url(#sc-h3)"/>
	<g class="sc-stones">' . $st . '</g>
	<g>' . $heather . '</g>
</svg>';
}
