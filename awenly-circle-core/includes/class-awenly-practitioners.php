<?php
if (!defined('ABSPATH')) exit;
class Awenly_Practitioners {
    public function __construct() {
        add_action('init',[$this,'register_types']); add_action('add_meta_boxes',[$this,'meta_boxes']);
        add_action('save_post_awenly_practitioner',[$this,'save_meta']);
        add_shortcode('awenly_practitioner_directory',[$this,'directory_shortcode']);
        add_shortcode('awenly_practitioner_profile',[$this,'profile_shortcode']);
        add_filter('the_content',[$this,'append_details'],20);
    }
    public function register_types() {
        register_post_type('awenly_practitioner',['labels'=>['name'=>'Practitioners','singular_name'=>'Practitioner','add_new_item'=>'Add Practitioner','edit_item'=>'Edit Practitioner'],'public'=>true,'has_archive'=>true,'rewrite'=>['slug'=>'practitioners'],'menu_icon'=>'dashicons-id','supports'=>['title','editor','thumbnail','excerpt'],'show_in_rest'=>true]);
        register_taxonomy('awenly_specialty','awenly_practitioner',['labels'=>['name'=>'Specialisations','singular_name'=>'Specialisation'],'public'=>true,'hierarchical'=>true,'show_in_rest'=>true,'rewrite'=>['slug'=>'practitioner-specialty']]);
    }
    public function meta_boxes() { add_meta_box('awenly_practitioner_details','Practitioner details',[$this,'meta_box'],'awenly_practitioner','normal','high'); }
    public function meta_box($post) {
        wp_nonce_field('awenly_practitioner_save','awenly_practitioner_nonce');
        $fields=['fee'=>'Session price (e.g. €70)','mode'=>'Session format (online, in person or both)','location'=>'Location','booking_url'=>'Booking URL','qualifications'=>'Qualifications'];
        foreach($fields as $key=>$label) { $value=get_post_meta($post->ID,'_awenly_'.$key,true); echo '<p><label style="display:block;font-weight:600;margin-bottom:5px" for="awenly_'.$key.'">'.esc_html($label).'</label><input style="width:100%;max-width:650px" id="awenly_'.$key.'" name="awenly_'.$key.'" value="'.esc_attr($value).'"></p>'; }
    }
    public function save_meta($post_id) {
        if(!isset($_POST['awenly_practitioner_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['awenly_practitioner_nonce'])),'awenly_practitioner_save')) return;
        if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) return; if(!current_user_can('edit_post',$post_id)) return;
        foreach(['fee','mode','location','qualifications'] as $key) if(isset($_POST['awenly_'.$key])) update_post_meta($post_id,'_awenly_'.$key,sanitize_text_field(wp_unslash($_POST['awenly_'.$key])));
        if(isset($_POST['awenly_booking_url'])) update_post_meta($post_id,'_awenly_booking_url',esc_url_raw(wp_unslash($_POST['awenly_booking_url'])));
    }
    public function directory_shortcode($atts) {
        $atts=shortcode_atts(['per_page'=>12],$atts); $search=sanitize_text_field(wp_unslash($_GET['practitioner_search']??'')); $specialty=absint($_GET['specialty']??0); $mode=sanitize_text_field(wp_unslash($_GET['mode']??''));
        $args=['post_type'=>'awenly_practitioner','post_status'=>'publish','posts_per_page'=>min(48,max(1,absint($atts['per_page']))),'s'=>$search];
        if($specialty) $args['tax_query']=[['taxonomy'=>'awenly_specialty','field'=>'term_id','terms'=>$specialty]];
        if($mode) $args['meta_query']=[['key'=>'_awenly_mode','value'=>$mode,'compare'=>'LIKE']];
        $q=new WP_Query($args); $terms=get_terms(['taxonomy'=>'awenly_specialty','hide_empty'=>false]); ob_start(); ?>
        <div class="awenly-directory"><form class="awenly-filters" method="get">
        <label class="screen-reader-text" for="practitioner_search">Search practitioners</label><input id="practitioner_search" name="practitioner_search" placeholder="Name or keyword" value="<?php echo esc_attr($search); ?>">
        <label class="screen-reader-text" for="awenly-specialty">Specialisation</label><select id="awenly-specialty" name="specialty"><option value="">All specialisations</option><?php foreach($terms as $term): ?><option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($specialty,$term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select>
        <label class="screen-reader-text" for="awenly-mode">Session format</label><select id="awenly-mode" name="mode"><option value="">Any format</option><option value="Online" <?php selected($mode,'Online'); ?>>Online</option><option value="In person" <?php selected($mode,'In person'); ?>>In person</option><option value="Both" <?php selected($mode,'Both'); ?>>Both</option></select><button class="awenly-btn" type="submit">Search</button></form>
        <div class="awenly-practitioner-grid"><?php if($q->have_posts()): while($q->have_posts()): $q->the_post(); $fee=get_post_meta(get_the_ID(),'_awenly_fee',true); $mode_value=get_post_meta(get_the_ID(),'_awenly_mode',true); ?>
        <article class="awenly-practitioner-card"><a class="awenly-practitioner-image" href="<?php the_permalink(); ?>"><?php if(has_post_thumbnail()) the_post_thumbnail('medium'); else echo '<span class="awenly-avatar-mark">A</span>'; ?></a><div class="awenly-practitioner-body"><small>PRACTITIONER</small><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(wp_trim_words(get_the_excerpt()?:get_the_content(),22)); ?></p><div class="awenly-meta"><?php if($mode_value) echo '<span>'.esc_html($mode_value).'</span>'; if($fee) echo '<span>'.esc_html($fee).'</span>'; ?></div><a class="awenly-text-link" href="<?php the_permalink(); ?>">View profile →</a></div></article>
        <?php endwhile; wp_reset_postdata(); else: ?><p class="awenly-empty">No practitioner profiles are published yet. Please check back soon.</p><?php endif; ?></div></div><?php return ob_get_clean();
    }
    public function profile_shortcode() {
        if(!is_singular('awenly_practitioner')) return ''; $id=get_the_ID(); $booking=get_post_meta($id,'_awenly_booking_url',true);
        $items=['Session price'=>'fee','Format'=>'mode','Location'=>'location','Qualifications'=>'qualifications']; ob_start(); ?><section class="awenly-profile">
        <?php if(has_post_thumbnail()) echo get_the_post_thumbnail($id,'large',['class'=>'awenly-profile-photo']); ?><div><small>AWENLY CIRCLE PRACTITIONER</small><h1><?php echo esc_html(get_the_title($id)); ?></h1>
        <div class="awenly-profile-copy"><?php echo wpautop(wp_kses_post(strip_shortcodes(get_post_field('post_content',$id)))); ?></div><dl><?php foreach($items as $label=>$key): $v=get_post_meta($id,'_awenly_'.$key,true); if($v) echo '<div><dt>'.esc_html($label).'</dt><dd>'.esc_html($v).'</dd></div>'; endforeach; ?></dl>
        <?php if($booking) echo '<a class="awenly-btn" href="'.esc_url($booking).'" target="_blank" rel="noopener noreferrer">Book a session</a>'; ?></div></section><?php return ob_get_clean();
    }
    /** Show price, format, location, qualifications and the booking button under the content of a single practitioner. */
    public function append_details($content) {
        static $running = false;
        if ($running || !is_singular('awenly_practitioner') || !in_the_loop() || !is_main_query()) return $content;
        if (has_shortcode(get_post_field('post_content', get_the_ID()), 'awenly_practitioner_profile')) return $content;
        $running = true;
        $id = get_the_ID(); $booking = get_post_meta($id, '_awenly_booking_url', true);
        $items = ['Session price'=>'fee','Format'=>'mode','Location'=>'location','Qualifications'=>'qualifications'];
        $html = '';
        foreach ($items as $label => $key) { $v = get_post_meta($id, '_awenly_'.$key, true); if ($v) $html .= '<div><dt>'.esc_html($label).'</dt><dd>'.esc_html($v).'</dd></div>'; }
        $out = $html ? '<dl class="awenly-details">'.$html.'</dl>' : '';
        if ($booking) $out .= '<p><a class="awenly-btn" href="'.esc_url($booking).'" target="_blank" rel="noopener noreferrer">Book a session</a></p>';
        $running = false;
        return $content . ($out ? '<section class="awenly-practitioner-details">'.$out.'</section>' : '');
    }
}
