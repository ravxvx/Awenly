<?php
/**
 * Data pulled from the practitioner's BuddyBoss profile: cover image, social networks,
 * community stats and public profile fields.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyBoss/BuddyPress profile cover image URL (or '').
 */
function awd_cover_url( $user_id ) {
	if ( ! $user_id || ! function_exists( 'bp_attachments_get_attachment' ) ) {
		return '';
	}
	$url = bp_attachments_get_attachment( 'url', array(
		'object_dir' => 'members',
		'item_id'    => (int) $user_id,
		'type'       => 'cover-image',
	) );
	return $url ? (string) $url : '';
}

/**
 * Social links: BuddyBoss "Social Networks" profile field + listing website.
 * Returns array of [ network => url ].
 */
function awd_social_links( $user_id ) {
	$links = array();
	if ( $user_id && function_exists( 'xprofile_get_field_data' ) ) {
		global $wpdb;
		$bp = function_exists( 'buddypress' ) ? buddypress() : null;
		if ( $bp && ! empty( $bp->profile->table_name_fields ) ) {
			$fields = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields} WHERE type = 'socialnetworks'" ); // phpcs:ignore
			foreach ( $fields as $fid ) {
				$data = xprofile_get_field_data( (int) $fid, (int) $user_id, 'array' );
				$data = maybe_unserialize( $data );
				if ( is_array( $data ) ) {
					foreach ( $data as $network => $url ) {
						$url = trim( (string) $url );
						if ( '' === $url ) {
							continue;
						}
						if ( ! preg_match( '#^https?://#i', $url ) ) {
							$url = 'https://' . ltrim( $url, '/' );
						}
						$links[ awd_detect_network( $network, $url ) ] = esc_url_raw( $url );
					}
				}
			}
		}
	}
	return array_filter( $links );
}

function awd_detect_network( $key, $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$map  = array( 'facebook' => 'facebook', 'fb.' => 'facebook', 'instagram' => 'instagram', 'linkedin' => 'linkedin', 'youtube' => 'youtube', 'youtu.be' => 'youtube', 'tiktok' => 'tiktok', 'pinterest' => 'pinterest', 'twitter' => 'x', 'x.com' => 'x' );
	foreach ( $map as $needle => $net ) {
		if ( false !== strpos( $host, $needle ) ) {
			return $net;
		}
	}
	$key = sanitize_key( $key );
	return ( '' === $key || is_numeric( $key ) ) ? 'link-' . substr( md5( $url ), 0, 6 ) : $key;
}

function awd_social_icon( $network ) {
	$n = strtolower( preg_replace( '/^link-.*/', 'website', $network ) );
	$i = array(
		'facebook'  => '<path d="M14 8h3V4h-3c-2.8 0-4 1.7-4 4.2V10H7v4h3v8h4v-8h3l1-4h-4V8.6c0-.4.3-.6.7-.6z" fill="currentColor"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/>',
		'twitter'   => '<path d="M4 4l16 16M20 4L4 20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',
		'x'         => '<path d="M4 4l16 16M20 4L4 20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',
		'linkedin'  => '<path d="M5 9h3v11H5zM6.5 4a1.8 1.8 0 1 1 0 3.6 1.8 1.8 0 0 1 0-3.6zM10 9h3v1.6c.5-.9 1.7-1.9 3.6-1.9 3.3 0 3.9 2.1 3.9 4.9V20h-3v-5.6c0-1.4 0-3.1-1.9-3.1S13 12.8 13 14.3V20h-3z" fill="currentColor"/>',
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4" fill="currentColor"/><path d="M10 9l5 3-5 3z" fill="#fff"/>',
		'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3-3.46V8a6.5 6.5 0 1 0 6 6.5V9.5a7 7 0 0 0 4 1.3v-3A4 4 0 0 1 17 3z" fill="currentColor"/>',
		'pinterest' => '<path d="M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.2-2 0-2.9l1.3-5.4s-.3-.7-.3-1.6c0-1.5.9-2.7 2-2.7.9 0 1.4.7 1.4 1.5 0 .9-.6 2.3-.9 3.6-.3 1.1.5 2 1.6 2 1.9 0 3.4-2 3.4-4.9 0-2.6-1.9-4.4-4.5-4.4a4.7 4.7 0 0 0-4.9 4.7c0 .9.4 1.9.8 2.5l.1.4-.3 1.2c0 .2-.2.3-.4.2-1.3-.6-2.2-2.6-2.2-4.2 0-3.4 2.5-6.6 7.2-6.6 3.8 0 6.7 2.7 6.7 6.3 0 3.7-2.4 6.8-5.6 6.8-1.1 0-2.2-.6-2.5-1.3l-.7 2.6c-.3 1-.9 2.2-1.4 2.9A10 10 0 1 0 12 2z" fill="currentColor"/>',
		'website'   => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z" fill="none" stroke="currentColor" stroke-width="2"/>',
	);
	return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ( $i[ $n ] ?? $i['website'] ) . '</svg>';
}

/**
 * Community stats from BuddyBoss.
 */
function awd_bp_stats( $user_id ) {
	$stats = array();
	$u     = get_userdata( $user_id );
	if ( $u ) {
		$stats[] = array( __( 'Member since', 'awenly-directory' ), date_i18n( 'M Y', strtotime( $u->user_registered ) ) );
	}
	if ( function_exists( 'friends_get_total_friend_count' ) && function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) ) {
		$stats[] = array( __( 'Connections', 'awenly-directory' ), number_format_i18n( (int) friends_get_total_friend_count( $user_id ) ) );
	}
	if ( function_exists( 'bp_get_total_group_count_for_user' ) && bp_is_active( 'groups' ) ) {
		$stats[] = array( __( 'Groups', 'awenly-directory' ), number_format_i18n( (int) bp_get_total_group_count_for_user( $user_id ) ) );
	}
	if ( function_exists( 'bp_get_user_last_activity' ) ) {
		$last = bp_get_user_last_activity( $user_id );
		if ( $last ) {
			$stats[] = array( __( 'Last active', 'awenly-directory' ), sprintf( __( '%s ago', 'awenly-directory' ), human_time_diff( strtotime( $last . ' UTC' ), time() ) ) );
		}
	}
	return $stats;
}

/**
 * Public, filled-in BuddyBoss profile fields (excluding name, socials, dates).
 */
function awd_bp_fields( $user_id, $limit = 8 ) {
	if ( ! function_exists( 'bp_xprofile_get_groups' ) ) {
		return array();
	}
	$groups = bp_xprofile_get_groups( array(
		'user_id'                => $user_id,
		'fetch_fields'           => true,
		'fetch_field_data'       => true,
		'fetch_visibility_level' => true,
		'hide_empty_fields'      => true,
	) );
	$skip_types = array( 'socialnetworks', 'datebox', 'membertypes', 'telephone', 'gender' );
	$name_ids   = array();
	foreach ( array( 'bp_xprofile_firstname_field_id', 'bp_xprofile_lastname_field_id', 'bp_xprofile_nickname_field_id' ) as $fn ) {
		if ( function_exists( $fn ) ) {
			$name_ids[] = (int) call_user_func( $fn );
		}
	}
	$out = array();
	foreach ( (array) $groups as $g ) {
		foreach ( (array) ( $g->fields ?? array() ) as $f ) {
			if ( 1 === (int) $f->id || in_array( (int) $f->id, $name_ids, true ) || in_array( $f->type, $skip_types, true ) ) {
				continue;
			}
			if ( isset( $f->visibility_level ) && 'public' !== $f->visibility_level ) {
				continue;
			}
			$val = isset( $f->data->value ) ? maybe_unserialize( $f->data->value ) : '';
			$val = is_array( $val ) ? implode( ', ', array_filter( array_map( 'strval', $val ) ) ) : (string) $val;
			$val = trim( wp_strip_all_tags( $val ) );
			if ( '' === $val ) {
				continue;
			}
			$out[] = array( $f->name, $val );
			if ( count( $out ) >= $limit ) {
				return $out;
			}
		}
	}
	return $out;
}
