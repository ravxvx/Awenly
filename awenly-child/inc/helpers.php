<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defaults for every Customizer option.
 */
function awenly_defaults() {
	return array(
		'topbar_text'     => 'A mindful community for personal growth, spirituality and healing.',
		'social_facebook' => 'https://www.facebook.com/LinkInEvents.net',
		'social_instagram'=> '',
		'social_youtube'  => '',
		'social_linkedin' => '',
		'join_url'        => home_url( '/register/' ),
		'login_url'       => wp_login_url(),
		'hero_image'      => '',
		'hero_tagline'    => 'Community • Learning • Healing',
		'hero_title'      => "Real People.\nMeaningful Connections.\nLasting Change.",
		'hero_text'       => 'A supportive space to explore wellness, consciousness and personal growth together.',
		'community_url'   => home_url( '/news-feed/' ),
		'events_url'      => function_exists( 'tribe_get_events_link' ) ? tribe_get_events_link() : home_url( '/event-directory/' ),
		'practitioners_url' => home_url( '/practitioners/' ),
		'courses_url'     => home_url( '/videos/' ),
		'events_count'    => 3,
	);
}

/**
 * Read one option (theme_mod) with its default.
 */
function awenly_opt( $key ) {
	$d = awenly_defaults();
	return get_theme_mod( 'awenly_' . $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

/**
 * Awen symbol logo mark (three dots + three rays in a circle), inline SVG.
 */
function awenly_logo_mark( $size = 56, $class = 'awenly-mark' ) {
	$size = (int) $size;
	return '<svg class="' . esc_attr( $class ) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100" aria-hidden="true" focusable="false">'
		. '<defs><linearGradient id="awg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#f6c38b"/></linearGradient></defs>'
		. '<circle cx="50" cy="50" r="46" fill="none" stroke="currentColor" stroke-width="4"/>'
		. '<circle cx="35" cy="28" r="4.5" fill="currentColor"/><circle cx="50" cy="24" r="4.5" fill="currentColor"/><circle cx="65" cy="28" r="4.5" fill="currentColor"/>'
		. '<path d="M33 38 L22 72 L28 74 L37 40 Z" fill="currentColor"/>'
		. '<path d="M47 36 L46 82 L54 82 L53 36 Z" fill="url(#awg)" class="awenly-mark-ray"/>'
		. '<path d="M67 38 L78 72 L72 74 L63 40 Z" fill="currentColor"/>'
		. '</svg>';
}

/**
 * Simple line icons used on the homepage.
 */
function awenly_icon( $name ) {
	$icons = array(
		'connect'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
		'learn'    => '<path d="M12 21c-4-2-7-5-7-9 2 0 5 1 7 4 2-3 5-4 7-4 0 4-3 7-7 9z"/><path d="M12 16c-1.5-2-1.5-6 0-9 1.5 3 1.5 7 0 9z"/>',
		'heal'     => '<path d="M5 19c0-8 6-14 15-14 0 9-6 15-14 15"/><path d="M5 19 13 11"/>',
		'grow'     => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>',
		'pin'      => '<path d="M12 22s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2a5 5 0 0 1 6 4.8"/>',
		'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'video'    => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="M16 10l6-3v10l-6-3z"/>',
		'check'    => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.6 2.6L16 9.6"/>',
	);
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	return '<svg class="awenly-icon awenly-icon-' . esc_attr( $name ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $name ] . '</svg>';
}

/**
 * Social icons (filled, brand-neutral shapes).
 */
function awenly_social_icon( $network ) {
	$paths = array(
		'facebook'  => '<path d="M14 8h3V4h-3c-2.8 0-4 1.8-4 4.3V11H7v4h3v9h4v-9h3l1-4h-4V8.6c0-.4.3-.6.6-.6z"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.3"/>',
		'youtube'   => '<path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-3.8 31 31 0 0 0-.4-3.8zM10 15V9l5.2 3z"/>',
		'linkedin'  => '<path d="M4.5 3.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM3 9h3v12H3zM9 9h2.9v1.7c.5-.9 1.7-2 3.6-2 3.8 0 4.5 2.5 4.5 5.7V21h-3v-5.8c0-1.4 0-3.2-2-3.2s-2.2 1.5-2.2 3.1V21H9z"/>',
	);
	return isset( $paths[ $network ] )
		? '<svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true" focusable="false">' . $paths[ $network ] . '</svg>'
		: '';
}
