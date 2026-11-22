<?php
/**
 * FAQ sections for the November 2026 articles: appended once to the post content (with a further-reading
 * link), plus FAQPage structured data for any post that has an _awd_faq list.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AWD_BLOG_FAQ_VERSION = 1;

function awd_blog_faq_run() {
	$file = AWD_DIR . 'data/blog-faq-2026-11.json';
	$data = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
	$titles = array(
		11296 => 'How to Choose a Holistic Practitioner: 7 Simple Steps',
		11297 => 'What Is a Sound Bath? Benefits and What to Expect',
		11298 => 'Reiki Explained: What Happens in a Reiki Session',
		11299 => 'Breathwork for Beginners: 5 Calming Techniques',
		11300 => 'Healing Circle Guide: What It Is and Why People Join',
		11301 => 'Meditation for Beginners: A Gentle 7-Day Plan',
		11302 => 'Holistic Wellbeing: Simple Habits for Mind, Body, Spirit',
		11303 => 'Wellness Retreat Guide: How to Get the Most from It',
	);
	foreach ( (array) $data as $id => $f ) {
		$id   = (int) $id;
		$post = get_post( $id );
		if ( ! $post || 'post' !== $post->post_type ) {
			continue;
		}
		if ( false === strpos( $post->post_content, 'awd-faq' ) ) {
			$html  = "\n\n<!-- wp:group {\"className\":\"awd-faq\"} -->\n<div class=\"wp-block-group awd-faq\">";
			$html .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $f['h'] ) . "</h2>\n<!-- /wp:heading -->\n\n";
			foreach ( $f['qa'] as $qa ) {
				$html .= "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">" . esc_html( $qa[0] ) . "</h3>\n<!-- /wp:heading -->\n\n";
				$html .= "<!-- wp:paragraph -->\n<p>" . esc_html( $qa[1] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
			}
			$html .= "<!-- wp:paragraph -->\n<p><strong>Further reading:</strong> " . esc_html( $f['ext'][2] ) . ' <a href="' . esc_url( $f['ext'][1] ) . '" target="_blank" rel="noopener">' . esc_html( $f['ext'][0] ) . "</a>.</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->";
			wp_update_post( array( 'ID' => $id, 'post_content' => $post->post_content . $html ) );
		}
		update_post_meta( $id, '_awd_faq', $f['qa'] );
		if ( isset( $titles[ $id ] ) ) {
			update_post_meta( $id, 'rank_math_title', $titles[ $id ] );
		}
	}
	do_action( 'litespeed_purge_all' );
}

add_action( 'init', function () {
	if ( (int) get_option( 'awd_blog_faq_v', 0 ) >= AWD_BLOG_FAQ_VERSION || get_transient( 'awd_blog_faq_lock' ) ) {
		return;
	}
	set_transient( 'awd_blog_faq_lock', 1, 5 * MINUTE_IN_SECONDS );
	update_option( 'awd_blog_faq_v', AWD_BLOG_FAQ_VERSION, true );
	awd_blog_faq_run();
	delete_transient( 'awd_blog_faq_lock' );
}, 101 );

/* FAQPage structured data */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$qa = get_post_meta( get_the_ID(), '_awd_faq', true );
	if ( ! is_array( $qa ) || ! $qa ) {
		return;
	}
	$items = array();
	foreach ( $qa as $q ) {
		$items[] = array( '@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $q[1] ) );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items ) ) . "</script>\n";
}, 30 );
