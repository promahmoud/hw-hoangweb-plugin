<?php
/**
Plugin Name: Slider jcarousel
*/
?>
<?php
include_once('theme-setting.php');

?>
<div class="jcarousel-ml-skin slider-wrapper theme-default" id="jcarousel-slider-<?php echo $slider_id?>">
    <a href="#" class="prev">‹</a>
    <a href="#" class="next">›</a>
    <div class="hw-jcarousel jcarousellite">
    <ul>
    <?php
    if(!empty($query))
while ( $query->have_posts() ) {
    $query->next_post();
    $thumb = wp_get_attachment_image($query->post->ID,'full');
    $img = wp_get_attachment_image_src($query->post->ID,'full');
    if($img) $img_src = $img[0];
    else $img_src = plugins_url('asset/images/placeholder.png', __FILE__); //placeholder image

    //<img src=...
    //echo wp_get_attachment_image( $attachment_id, 'thumbnail' );

    $excerpt = str_replace(array("\r","\n"),"",$query->post->post_excerpt); #right way
    //get url
    $url=get_post_meta($query->post->ID,'hw-ml-slider_url',true);
    if(!$url) $url = plugins_url('asset/images/placeholder.png', __FILE__); //placeholder image
    ?>
    <li><a href="<?php echo $url //get_permalink($query->post->ID)?>"><img src="<?php echo $img_src?>" alt="<?php echo $excerpt?>" data-thumb="" data-transition="" alt="" title="<?php echo $excerpt?>"  /></a>
        <h2> <?php if($show_title) the_title();?></h2>
    </li>

<?php
}
?>
        </ul>

    </div>
</div>
<?php
//remove key not belong jcarousel option
$options = APF_hw_skin_Selector_hwskin::build_json_options($user_theme_options, 'template_file');

?>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        jQuery('#jcarousel-slider-<?php echo $slider_id?>').jCarouselLite(<?php echo ($options)?>);
    });
</script>