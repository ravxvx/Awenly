# Awenly Circle — BuddyBoss child theme

## Install
1. Back up the site (files + database).
2. Appearance → Themes → Add New → Upload Theme → `awenly-child.zip` → Install.
3. Click **Activate** on "Awenly Circle". BuddyBoss Theme must stay installed (it is the parent).
4. Appearance → Customize → **Awenly Circle**: hero image, texts, social links, URLs.
5. Logo in the header: Customize → BuddyBoss Theme → Header (or Site Identity) → upload the Awenly logo.

To undo: activate "BuddyBoss Theme" again. Nothing is deleted.

## What it does
- Purple top bar above the BuddyBoss header (social icons, tagline, Login / Join the Circle).
- New homepage (`front-page.php`): hero, Connect / Learn / Heal / Grow strip, Upcoming Events.
- Events come from the `awenly_event` post type (Awenly Circle plugin). Each event gets extra
  fields "Location" and "End time"; the featured image becomes the card photo.
- Community pages (profiles, groups, activity, messages) keep the BuddyBoss look.
