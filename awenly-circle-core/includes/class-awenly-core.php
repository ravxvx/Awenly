<?php
if (!defined('ABSPATH')) exit;
final class Awenly_Circle_Core {
    private static $instance = null;
    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {
        require_once AWENLY_CORE_PATH . 'includes/class-awenly-practitioners.php';
        require_once AWENLY_CORE_PATH . 'includes/class-awenly-events.php';
        new Awenly_Practitioners(); new Awenly_Events();
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('awenly_home', [$this, 'home_shortcode']);
        add_shortcode('awenly_community_links', [$this, 'community_shortcode']);
    }
    public static function activate() {
        require_once AWENLY_CORE_PATH . 'includes/class-awenly-practitioners.php';
        require_once AWENLY_CORE_PATH . 'includes/class-awenly-events.php';
        (new Awenly_Practitioners())->register_types(); (new Awenly_Events())->register_types();
        flush_rewrite_rules();
        if (!get_option('awenly_core_options')) add_option('awenly_core_options', [
            'headline'=>'Find your space. Meet your people. Grow together.',
            'subheadline'=>'A community for personal growth, healing and conscious living.',
            'directory_url'=>home_url('/practitioners/'), 'community_url'=>home_url('/activity/'),
            'events_url'=>home_url('/events/'), 'register_url'=>home_url('/register/')
        ]);
    }
    public static function deactivate() { flush_rewrite_rules(); }
    public function assets() { wp_enqueue_style('awenly-core', AWENLY_CORE_URL.'assets/css/awenly-core.css', [], AWENLY_CORE_VERSION); }
    public function options() {
        return wp_parse_args(get_option('awenly_core_options', []), [
            'headline'=>'Find your space. Meet your people. Grow together.',
            'subheadline'=>'A community for personal growth, healing and conscious living.',
            'directory_url'=>home_url('/practitioners/'), 'community_url'=>home_url('/activity/'),
            'events_url'=>home_url('/events/'), 'register_url'=>home_url('/register/')
        ]);
    }
    public function admin_menu() { add_menu_page('Awenly Circle','Awenly Circle','manage_options','awenly-circle',[$this,'settings_page'],'dashicons-heart',58); }
    public function register_settings() { register_setting('awenly_core_group','awenly_core_options',['sanitize_callback'=>[$this,'sanitize_options']]); }
    public function sanitize_options($input) {
        $out=['headline'=>sanitize_text_field($input['headline']??''),'subheadline'=>sanitize_textarea_field($input['subheadline']??'')];
        foreach(['directory_url','community_url','events_url','register_url'] as $key) $out[$key]=esc_url_raw($input[$key]??'');
        return $out;
    }
    public function settings_page() {
        if (!current_user_can('manage_options')) return; $o=$this->options(); ?>
        <div class="wrap awenly-admin"><h1>Awenly Circle Core</h1><p>Configure the public Awenly experience. BuddyBoss remains responsible for member accounts, activity, groups and messaging.</p>
        <form method="post" action="options.php"><?php settings_fields('awenly_core_group'); ?><table class="form-table"><tbody>
        <tr><th><label for="awenly-headline">Homepage headline</label></th><td><input class="regular-text" id="awenly-headline" name="awenly_core_options[headline]" value="<?php echo esc_attr($o['headline']); ?>"></td></tr>
        <tr><th><label for="awenly-subheadline">Homepage description</label></th><td><textarea class="large-text" rows="3" id="awenly-subheadline" name="awenly_core_options[subheadline]"><?php echo esc_textarea($o['subheadline']); ?></textarea></td></tr>
        <?php foreach(['directory_url'=>'Practitioner directory URL','community_url'=>'BuddyBoss community/activity URL','events_url'=>'Events URL','register_url'=>'Registration URL'] as $key=>$label): ?>
        <tr><th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th><td><input class="regular-text" type="url" id="<?php echo esc_attr($key); ?>" name="awenly_core_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($o[$key]); ?>"></td></tr>
        <?php endforeach; ?></tbody></table><?php submit_button('Save Awenly settings'); ?></form>
        <hr><h2>Available shortcodes</h2><code>[awenly_home]</code>, <code>[awenly_practitioner_directory]</code>, <code>[awenly_practitioner_profile]</code>, <code>[awenly_events]</code>, <code>[awenly_community_links]</code></div><?php
    }
    public function home_shortcode() {
        $o=$this->options(); ob_start(); ?>
        <main class="awenly-shell"><section class="awenly-hero"><div class="awenly-kicker">AWENLY CIRCLE <span>•</span> COMMUNITY FOR CONSCIOUS LIVING</div>
        <h1><?php echo esc_html($o['headline']); ?></h1><p><?php echo esc_html($o['subheadline']); ?></p>
        <div class="awenly-actions"><a class="awenly-btn" href="<?php echo esc_url($o['register_url']); ?>">Join the circle</a><a class="awenly-link-btn" href="<?php echo esc_url($o['directory_url']); ?>">Find a practitioner <span>↗</span></a></div>
        <div class="awenly-orbit" aria-hidden="true"><span></span><span></span><span></span><i></i></div></section>
        <section class="awenly-feature-grid">
        <a class="awenly-feature" href="<?php echo esc_url($o['community_url']); ?>"><small>01 / CONNECT</small><h2>Community</h2><p>Meet people, join circles and share what matters.</p><b>Explore community →</b></a>
        <a class="awenly-feature" href="<?php echo esc_url($o['directory_url']); ?>"><small>02 / DISCOVER</small><h2>Practitioners</h2><p>Find therapists, coaches and guides for your journey.</p><b>Find your guide →</b></a>
        <a class="awenly-feature" href="<?php echo esc_url($o['events_url']); ?>"><small>03 / EXPERIENCE</small><h2>Events & learning</h2><p>Explore workshops, courses and live gatherings.</p><b>See what's happening →</b></a></section>
        <section class="awenly-bottom-cta"><div><small>YOUR NEXT CHAPTER STARTS HERE</small><h2>There is space for you in the circle.</h2></div><a class="awenly-btn" href="<?php echo esc_url($o['register_url']); ?>">Become a member</a></section></main><?php
        return ob_get_clean();
    }
    public function community_shortcode() {
        $o=$this->options(); return '<div class="awenly-community-links"><a href="'.esc_url($o['community_url']).'">Activity feed</a><a href="'.esc_url(home_url('/groups/')).'">Groups</a><a href="'.esc_url(home_url('/members/')).'">Members</a><a href="'.esc_url(home_url('/messages/')).'">Messages</a></div>';
    }
}
