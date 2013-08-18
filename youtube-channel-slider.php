<?php
/**
 * @package youtube_channel_slider
 */
/*
Plugin Name: Youtube Channel Slider
Plugin URI: http://www.kesweh.com/wordpress-plugins/
Description: Show slider with thumbs extracted from Youtube Channel
Version: 0.1
Author: Shafie Abla
Author URI: http://www.kesweh.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
define('YCS_VERSION', '0.1');

//To perform action while activating pulgin i.e. creating the thumbnail of first image of  all posts
register_activation_hook( __FILE__, 'ycs_activate' );

//Create menu for configure page
add_action('admin_menu', 'ycs_admin_actions');
add_action('admin_print_styles', 'ycs_admin_style');

//Add  the nedded styles & script
add_action('wp_print_styles', 'ycs_add_style');
add_action('wp_head', 'ycs_add_custom_style');
add_action('init', 'ycs_add_script');

add_shortcode('ycs', 'ycs_show');

// register Rps widget
add_action('widgets_init', create_function('', 'return register_widget("RpsWidget");'));

if ( file_exists(ABSPATH . WPINC . '/class-simplepie.php') ) {
		@require_once (ABSPATH . WPINC . '/class-simplepie.php');
	} else {
		die (__('Error in file: ' . __FILE__ . ' on line: ' . __LINE__ . '.<br />The WordPress file "class-simplepie.php" with class SimplePie could not be included.'));
	} 
	
/** 
*Set the default options while activating the pugin & create thumbnails of first image of all the posts
*/
function ycs_activate() {
	$width = get_option('ycs_width');
	if ( empty($width) ) {
		$width = '500';
		update_option('ycs_width', $width);
	}
	
	$height = get_option('ycs_height');
	if ( empty($height) ) {
		$height = '250';
		update_option('ycs_height', $height);
	}

	$ycs_slider_speed = get_option('ycs_slider_speed');
	if ( empty($ycs_slider_speed) ) {
		$ycs_slider_speed = '3';
		update_option('ycs_slider_speed', $ycs_slider_speed);
	}
	
	$post_per_slide = get_option('ycs_post_per_slide');
	if ( empty($post_per_slide) ) {
		$post_per_slide = '2';
		update_option('ycs_post_per_slide', $post_per_slide);
	}
	
	$total_posts = get_option('ycs_total_posts');
	if ( empty($total_posts) ) {
		$total_posts = '6';
		update_option('ycs_total_posts', $total_posts);
	}
	
	$slider_content = get_option('ycs_slider_content');
	if ( empty($slider_content) ) {
		$slider_content = '1';
		update_option('ycs_slider_content', $slider_content);
	}
}


/** Create menu for options page */
function ycs_admin_actions() {
    add_options_page('Youtube Channel Slider', 'Youtube Channel Slider', 'manage_options', 'youtube-channel-slider', 'ycs_admin');
}

/** To perform admin page functionality */
function ycs_admin() {
    if ( !current_user_can('manage_options') )
    	wp_die( __('You do not have sufficient permissions to access this page.') );
	include('youtube-channel-slider-admin.php');
}

function ycs_admin_style() {	
	wp_enqueue_style('rps-admin-style', WP_PLUGIN_URL.'/youtube-channel-slider/css/rps-admin-style.css');
}

/** Link the needed stylesheet */
function ycs_add_style() {
	wp_enqueue_style('rps-style', WP_PLUGIN_URL.'/youtube-channel-slider/css/style.css');
}

function ycs_add_custom_style() {
	echo "<style type=\"text/css\" media=\"screen\">" . stripslashes(get_option('ycs_custom_css')) . "</style>";
}

/** Link the needed script */
function ycs_add_script() {
	if ( !is_admin() ){
		wp_enqueue_script( 'jquery' );
	}
}

/** To show slider 
 * @return output
*/
function ycs_show() {	
	$width = get_option('ycs_width');
	$height = get_option('ycs_height');
	$post_per_slide = get_option('ycs_post_per_slide');
	$total_posts = get_option('ycs_total_posts');
	$slider_content = get_option('ycs_slider_content');
	$channel_name = get_option('ycs_channel_name');	
	$post_title_color = get_option('ycs_post_title_color');
	$post_title_bg_color = get_option('ycs_post_title_bg_color');
	$slider_speed = get_option('ycs_slider_speed');
	$pagination_style = get_option('ycs_pagination_style');
	$excerpt_words = get_option('ycs_excerpt_words');
	$show_post_date = get_option('ycs_show_post_date');
	$post_date_text = get_option('ycs_post_date_text');
	$post_date_format = get_option('ycs_post_date_format');
	
	if(empty($post_date_text)){
		$post_date_text = "Posted On:";
	}
	
	if(empty($post_date_format)){
		$post_date_format = "j-F-Y";
	}
	
	if ( empty($slider_speed) ) {
		$slider_speed = 7000;
	}else{
		$slider_speed = $slider_speed * 1000;
	}
	if ( empty($post_title_color) ){
		$post_title_color = "#666";
	}else{
		$post_title_color = "#".$post_title_color;
	}
	$post_title_bg_color_js = "";
	if ( !empty($post_title_bg_color) ){
		$post_title_bg_color_js = "#".$post_title_bg_color;
	}
	$excerpt_length = '';
	$excerpt_length = abs( (($width-40)/20) * (($height-55)/15) );
	
	$user_name = $channel_name;  
	$feed = new SimplePie();
	$feed->set_feed_url("http://gdata.youtube.com/feeds/api/users/".$user_name."/uploads");
	$feed->enable_cache(false); //  disable caching
	$feed->set_timeout(5);
	$success = $feed->init();
	$feed->handle_content_type();
	$YT_PlayerPage = "http://www.youtube.com/user/".$user_name."#play/uploads/";
	$i=0;
	$post_details = NULL;
	
	if ( count($feed->get_items())< $total_posts ) {
		$total_posts	= count($feed->get_items());
	}
	
	if ( ($total_posts%$post_per_slide)==0 )
		$paging  = $total_posts/$post_per_slide; 
	else
		$paging  = ($total_posts/$post_per_slide) + 1; 
		
	foreach ($feed->get_items() as $item) {
		if ($enclosure = $item->get_enclosure()) {
			$YT_VideoID = substr(strstr($item->get_permalink(), 'v='), 2, 11);
			$post_details[$i]['post_title'] = $item->get_title();
			$post_details[$i]['post_permalink'] = $YT_PlayerPage . $YT_VideoNumber . "/" . $YT_VideoID;
			$post_details[$i]['post_first_img'] = $enclosure->get_thumbnail();
			$post_details[$i]['post_excerpt'] =$item->get_description();
		$i++;
		if($YT_VideoNumber == $total_posts) break;
		$YT_VideoNumber++;
			#if ( $show_post_date ){
			#	$post_details[$key]['post_date'] = date($post_date_format,strtotime($val->post_date));	
			#}
		}
	}
	
	//$upload_dir = wp_upload_dir();
	$output = '<!--Automatic Image Slider w/ CSS & jQuery with some customization-->';
	$output .='<script type="text/javascript">
	$j = jQuery.noConflict();
	$j(document).ready(function() {';

	//Set Default State of each portfolio piece
	if ($pagination_style != '3' ){
		$output .='$j("#rps .paging").show();';
	}
	$output .='$j("#rps .paging a:first").addClass("active");
	
	$j(".slide").css({"width" : '.$width.'});
	$j("#rps .window").css({"width" : '.($width).'});
	$j("#rps .window").css({"height" : '.$height.'});
	$j(".col img").css({"width" : '.(($width/$post_per_slide)-15).'});
	$j("#rps .col").css({"width" : '.(($width/$post_per_slide)-2).'});
	$j("#rps .col").css({"height" : '.($height-4).'});
	$j("#rps .col p.post-title span").css({"color" : "'.($post_title_color).'"});
	$j("#rps .post-date").css({"top" : '.($height-20).'});
	$j("#rps .post-date").css({"width" : '.(($width/$post_per_slide)-12).'});';
	
	if (!empty($post_title_bg_color_js)){
		$output .='$j("#rps .col p.post-title").css({"background-color" : "'.($post_title_bg_color_js).'"});';
	}
	
	$output .='var imageWidth = $j("#rps .window").width();
	//var imageSum = $j("#rps .slider div").size();
	var imageReelWidth = imageWidth * '.$paging.';
	
	//Adjust the image reel to its new size
	$j("#rps .slider").css({"width" : imageReelWidth});

	//Paging + Slider Function
	rotate = function(){	
		var triggerID = $active.attr("rel") - 1; //Get number of times to slide
		//alert(triggerID);
		var sliderPosition = triggerID * imageWidth; //Determines the distance the image reel needs to slide

		$j("#rps .paging a").removeClass("active"); 
		$active.addClass("active");
		
		//Slider Animation
		$j("#rps .slider").stop(true,false).animate({ 
			left: -sliderPosition
		}, 500 );
	}; 
	var play;
	//Rotation + Timing Event
	rotateSwitch = function(){		
		play = setInterval(function(){ //Set timer - this will repeat itself every 3 seconds
			$active = $j("#rps .paging a.active").next();
			if ( $active.length === 0) { //If paging reaches the end...
				$active = $j("#rps .paging a:first"); //go back to first
			}
			rotate(); //Trigger the paging and slider function
		}, '.$slider_speed.');
	};
	
	rotateSwitch(); //Run function on launch
	
	//On Hover
	$j("#rps .slider a").hover(function() {
		clearInterval(play); //Stop the rotation
	}, function() {
		rotateSwitch(); //Resume rotation
	});	
	
	//On Click
	$j("#rps .paging a").click(function() {	
		$active = $j(this); //Activate the clicked paging
		//Reset Timer
		clearInterval(play); //Stop the rotation
		rotate(); //Trigger rotation immediately
		rotateSwitch(); // Resume rotation
		return false; //Prevent browser jump to link anchor
	});	
});

</script>';
		
$output .= '<div id="rps">
            <div class="window">	
                <div class="slider">';
		$p=0;
		for ( $i = 1; $i <= $total_posts; $i+=$post_per_slide ) {
			$output .= '<div class="slide">';
					for ( $j = 1; $j <= $post_per_slide; $j++ ) {
						$output .= '<div class="col"><p class="post-title"><a href="'.$post_details[$p]['post_permalink'].'"><span>'.$post_details[$p]['post_title'].'</span></a></p>';
						if ( $slider_content == 2 ){
							$output .= '<p class="slider-content">'.$post_details[$p]['post_excerpt'];
							if($show_post_date){
								$output .= '<div class="post-date">'.$post_date_text.' '.$post_details[$p]['post_date'].'</div>';
							}
							$output .= '</p></div>';
						}elseif ( $slider_content == 1 ){
							$output .= '<p class="slider-content-img">';
							if( !empty($post_details[$p]['post_first_img']) ){
								$ycs_img_src_path = $post_details[$p]['post_first_img'];
								if(!empty($ycs_img_src_path)){
									$output .= '<a target="blank" href="'.$post_details[$p]['post_permalink'].'"><center><img src="'.$ycs_img_src_path.'" /></center></a>';
								}
							}
							if($show_post_date){
								$output .= '<div class="post-date">'.$post_date_text.' '.$post_details[$p]['post_date'].'</div>';
							}
							$output .= '</p></div>';			
						}elseif ( $slider_content == 3 ){
							$output .= '<p class="slider-content-both">';
							if( !empty($post_details[$p]['post_first_img']) || !empty($post_details[$p]['post_excerpt'])){
								$ycs_img_src_path = $post_details[$p]['post_first_img'];
								if(!empty($ycs_img_src_path)){
									$output .= '<a target="blank" href="'.$post_details[$p]['post_permalink'].'"><img src="'.$ycs_img_src_path.'" align="left" /></a>';
								}
								$output .= $post_details[$p]['post_excerpt'];
							}
							if($show_post_date){
								$output .= '<div class="post-date">'.$post_date_text.' '.$post_details[$p]['post_date'].'</div>';
							}
							$output .= '</p></div>';			
						}
						$p++;
						if ( $p == $total_posts )
							$p = 0;
					}
					$output .= '<div class="clr"></div>
				</div>';
		}
		$output .= '
                </div>
            </div>
            <div class="paging">';
				for ( $p = 1; $p <= $paging; $p++ ) {
					if( $pagination_style == '2' ){
						$output .= '<a href="#" rel="'.$p.'">&bull;</a>';
					}elseif( $pagination_style == '1' ){
						$output .= '<a href="#" rel="'.$p.'">'.$p.'</a>';
					}elseif( $pagination_style == '3' ){
						$output .= '<a href="#" rel="'.$p.'">&nbsp;</a>';
					}
				}
            $output .= '</div>
        </div><div class="rps-clr"></div>'; 
	return $output;
}

/** Create post excerpt manually
 * @param $post_content
 * @param $excerpt_length
 * @return post_excerpt or  void
*/
function create_excerpt( $post_content, $excerpt_length, $post_permalink, $excerpt_words=NULL){
	$keep_excerpt_tags = get_option('ycs_keep_excerpt_tags');
	
	if(!$keep_excerpt_tags){
		$post_excerpt = strip_shortcodes($post_content);
		$post_excerpt = str_replace(']]>', ']]&gt;', $post_excerpt);
		$post_excerpt = strip_tags($post_excerpt);
	}else{
		$post_excerpt = $post_content;
	}
	
	$link_text = get_option('ycs_link_text');
	if(!empty($link_text)){
		$more_link = $link_text;
	}else{
		$more_link = "[more]";
	}
	if( !empty($excerpt_words) ){	
		if ( !empty($post_excerpt) ) {
			$words = explode(' ', $post_excerpt, $excerpt_words + 1 );	
			array_pop($words);
			array_push($words, ' <a href="'.$post_permalink.'">'.$more_link.'</a>');
			$post_excerpt_rps = implode(' ', $words);
			return $post_excerpt_rps;
		} else {
			return;
		}
	}else{
		$post_excerpt_rps = substr( $post_excerpt, 0, $excerpt_length );
		if ( !empty($post_excerpt_rps) ) {
			if ( strlen($post_excerpt) > strlen($post_excerpt_rps) ){
				$post_excerpt_rps =substr( $post_excerpt_rps, 0, strrpos($post_excerpt_rps,' '));
			}	
			$post_excerpt_rps .= ' <a href="'.$post_permalink.'">'.$more_link.'</a>';
			return $post_excerpt_rps;
		} else {
			return;
		}
	}
}

/**
 * RpsWidget Class
 */
class RpsWidget extends WP_Widget {
    /** constructor */
    function RpsWidget() {
        parent::WP_Widget(false, $name = 'Youtube Channel Slider', array( 'description' => __( "Your youtube channel thumbs using slider") ));	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
	echo $before_widget;
        if ( $title )
		echo $before_title . $title . $after_title; 
		if (function_exists('ycs_show')) echo ycs_show(); 
		echo $after_widget; 
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }

} // class RpsWidget
?>
