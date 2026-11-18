<?php
if (!defined('ABSPATH')) exit;
class Awenly_Events {
    public function __construct() {
        add_action('init',[$this,'register_types']);
        add_action('add_meta_boxes',[$this,'meta_boxes']);
        add_action('save_post_awenly_event',[$this,'save_meta']);
        add_shortcode('awenly_events',[$this,'shortcode']);
    }
    public function register_types() {
        register_post_type('awenly_event',['labels'=>['name'=>'Awenly Events','singular_name'=>'Event','add_new_item'=>'Add Event','edit_item'=>'Edit Event'],'public'=>true,'has_archive'=>true,'rewrite'=>['slug'=>'circle-events'],'menu_icon'=>'dashicons-calendar-alt','supports'=>['title','editor','thumbnail','excerpt'],'show_in_rest'=>true]);
        register_post_meta('awenly_event','_awenly_event_start',['type'=>'string','single'=>true,'show_in_rest'=>true,'sanitize_callback'=>'sanitize_text_field','auth_callback'=>static function(){return current_user_can('edit_posts');}]);
    }
    public function meta_boxes() { add_meta_box('awenly_event_details','Event date & time',[$this,'meta_box'],'awenly_event','side','high'); }
    public function meta_box($post) {
        wp_nonce_field('awenly_event_save','awenly_event_nonce');
        $v=get_post_meta($post->ID,'_awenly_event_start',true);
        echo '<p><label for="awenly_event_start" style="display:block;font-weight:600;margin-bottom:5px">Starts</label><input type="datetime-local" id="awenly_event_start" name="awenly_event_start" value="'.esc_attr($v).'" style="width:100%"></p><p class="description">Site time zone. Events without a date are not listed.</p>';
    }
    public function save_meta($post_id) {
        if(!isset($_POST['awenly_event_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['awenly_event_nonce'])),'awenly_event_save')) return;
        if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) return; if(!current_user_can('edit_post',$post_id)) return;
        if(isset($_POST['awenly_event_start'])) {
            $v=sanitize_text_field(wp_unslash($_POST['awenly_event_start']));
            if($v==='' || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$v)) update_post_meta($post_id,'_awenly_event_start',$v);
        }
    }
    public function shortcode($atts) {
        $atts=shortcode_atts(['per_page'=>6],$atts);
        $now=current_time('Y-m-d\\TH:i');
        $q=new WP_Query(['post_type'=>'awenly_event','post_status'=>'publish','posts_per_page'=>min(48,max(1,absint($atts['per_page']))),'meta_key'=>'_awenly_event_start','orderby'=>'meta_value','order'=>'ASC','meta_query'=>[['key'=>'_awenly_event_start','value'=>$now,'compare'=>'>=','type'=>'CHAR']]]);
        ob_start(); ?><div class="awenly-event-grid"><?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); $start=get_post_meta(get_the_ID(),'_awenly_event_start',true); $ts=$start?strtotime($start):0; ?><article class="awenly-event-card"><small><?php echo $ts?esc_html(date_i18n('D j M · H:i',$ts)):'GATHER • LEARN • CONNECT'; ?></small><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt()?:get_the_content(),24)); ?></p><a class="awenly-text-link" href="<?php the_permalink(); ?>">Explore event →</a></article><?php endwhile; wp_reset_postdata(); else: ?><p class="awenly-empty">Upcoming gatherings will be listed here when published.</p><?php endif; ?></div><?php return ob_get_clean();
    }
}
