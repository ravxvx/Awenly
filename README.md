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

---

# Repository contents

| Folder | What it is | Live version |
|---|---|---|
| `awenly-circle-core/` | Original Core plugin (practitioner + event post types) | 1.0.1 |
| `awenly-child/` | **Awenly Circle** child theme for BuddyBoss Theme — homepage, header/footer, colours | 3.3.3 |
| `awenly-directory/` | **Awenly Practitioner Directory** plugin — self-service listings | 1.0.1 |
| `releases/` | Installable ZIPs | |

## Awenly Circle child theme (`awenly-child`)
- Requires **BuddyBoss Theme** (parent). Install via Appearance → Themes → Upload (`releases/awenly-child-3.3.3.zip`).
- Homepage (`front-page.php`): hero with join form, Next Live bar, feature cards, practitioner directory teaser, three steps, upcoming events, CTA.
- Reads events from **The Events Calendar** when active (falls back to the `awenly_event` post type).
- Site-wide: Awenly logo, lavender/lime styling, Awenly footer, branded login/register screens.
- Disables the old BuddyBoss "custom registration" redirect so `/register/` shows the sign-up form.
- Settings: Appearance → Customize → **Awenly Circle**. Hero/event images in `assets/img/` were generated with Canva.

## Awenly Practitioner Directory (`awenly-directory`)
- Uses the same `awenly_practitioner` post type and `awenly_specialty` taxonomy as Core (data is shared).
- **My Practice** tab on BuddyBoss profiles: members create/edit their own free listing.
- New listings go to **Pending**; admin gets an email, publishes → practitioner gets an email and the BuddyBoss profile type "Practitioner".
- Directory at `/practitioners/` with keyword, specialisation, city and format filters (`?s_practitioner=slug` supported for homepage chips).
- Listing page with booking link, BuddyBoss message button and community profile link.

### Roadmap
- Stage 2: reviews & ratings, "Verified" badge, practitioner's events on their listing.
- Stage 3 (optional): featured/paid listings, map.
