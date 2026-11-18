# Awenly Circle Core

A WordPress plugin foundation for the Awenly Circle community platform.

## Current status

Version 1.0.0 is an initial foundation/MVP, not a complete production platform.

### Included
- Awenly Circle admin settings for homepage copy and destination URLs
- `[awenly_home]` landing-page shortcode
- `[awenly_community_links]` BuddyBoss navigation shortcode
- Practitioner custom post type and specialty taxonomy
- `[awenly_practitioner_directory]` practitioner directory shortcode with basic filters
- Practitioner profile shortcode
- Event custom post type and `[awenly_events]` listing shortcode
- Responsive purple/lime styling

### Not included yet
- Booking and payment processing
- Practitioner self-service dashboards
- Complete event scheduling and registration workflows
- Full LearnDash/MemberPress integrations
- Production-ready automated tests and deployment workflow

## Installation
1. Download or clone this repository.
2. Place the `awenly-circle-core` directory in `wp-content/plugins/`.
3. Activate **Awenly Circle Core** in WordPress.
4. Configure the plugin under **Awenly Circle** in the WordPress admin.

## Shortcodes
- `[awenly_home]`
- `[awenly_community_links]`
- `[awenly_practitioner_directory]`
- `[awenly_practitioner_profile]`
- `[awenly_events]`

## Requirements
- WordPress with PHP support for modern plugin development
- BuddyBoss Platform is recommended for the community navigation integration

## Safety
Back up the WordPress database and files before installing or updating this plugin on a live site.
