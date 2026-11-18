<?php
if (!defined('ABSPATH')) exit;
class Awenly_Events {
    public function __construct() { add_action('init',[$this,'register_types']); add_shortcode('awenly_events',[$this,'shortcode']); }
    public function register_types() {
        register_post_type('awenly_event',['labels'=>['name'=>'Awenly Events','singular_name'=>'Event','add_new_item'=>'Add Event','edit_item'=>'Edit Event'],'public'=>true,'has_archive'=>true,'rewrite'=>['slug'=>'circle-events'],'menu_icon'=>'dashicons-calendar-alt','supports'=>['title','editor','thumbnail','excerpt'],'show_in_rest'=>true]);
        register_post_meta('awenly_event','_awenly_event_start',['type'=>'string','single'=>true,'show_in_rest'=>true,'sanitize_callback'=>'sanitize_text_field','auth_callback'=>static function(){return current_user_can('edit_posts');}]);
        register_register_meta_placeholder();
    }
    public function shortcode($atts) {
        $q=new WP_Query(['post_type'=>'awenly_event','post_status'=>'publish','posts_per_page'=>absint($atts['per_page']??6),'meta_key'=>'_awenly_event_start','orderby'=>'meta_value','order'=>'ASC']);
        ob_start(); ?><div class="awenly-event-grid"><?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); ?><article class="awenly-event-card"><small>GATHER • LEARN • CONNECT</small><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt()?:get_the_content(),24)); ?></p><a class="awenly-text-link" href="<?php the_permalink(); ?>">Explore event →</a></article><?php endwhile; wp_reset_postdata(); else: ?><p class="awenly-empty">Upcoming gatherings will be listed here when published.</p><?php endif; ?></div><?php return ob_get_clean();
    }
}
function register_register_meta_placeholder() { /* Reserved for future event metadata extensions. */ }
