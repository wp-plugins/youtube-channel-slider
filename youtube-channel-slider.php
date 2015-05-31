<?php
/**
 * @package youtube_channel_slider
 */
/*
Plugin Name: Youtube Channel Slider
Plugin URI: http://www.kesweh.com/wordpress-plugins/
Description: Show slider with thumbs extracted from Youtube Channel
Version: 1.2
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
define('YCS_VERSION', '1');
define('YCS_URL', plugins_url('',__FILE__));

//To perform action while activating pulgin i.e. creating the thumbnail of first image of  all posts
register_activation_hook( __FILE__, 'ycs_activate' );

//Create menu for configure page
add_action('admin_menu', 'ycs_admin_actions');

//Add  the nedded styles & script
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



/** Link the needed script */
function ycs_add_script() {
	if ( !is_admin() ){
		wp_enqueue_script( 'jquery' );
	        
        wp_register_style('video-lightbox', YCS_URL.'/css/style.css');
        wp_enqueue_style('video-lightbox');
		wp_register_script('jquery.prettyphoto',YCS_URL.'/js/jquery.prettyPhoto.js', array('jquery'), '3.1.4');
        wp_enqueue_script('jquery.prettyphoto');
        wp_register_script('video-lightbox', YCS_URL.'/js/video-lightbox.js', array('jquery'), '3.1.4');
        wp_enqueue_script('video-lightbox');
        
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
	$default_thumb = get_option('ycs_default_thumb');
	$lightbox = get_option('ycs_lightbox');

	if ( empty($default_thumb) ){
		$default_thumb = "0.jpg";
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
	
	
	$user_name = $channel_name;  
	$feed = new SimplePie();
	$feed->set_feed_url("http://www.youtube.com/feeds/videos.xml?user=".$user_name);
	$feed->enable_cache(false); //  disable caching
	$feed->set_timeout(5);
	$success = $feed->init();
	$feed->handle_content_type();
	$YT_PlayerPage = "http://www.youtube.com/user/".$user_name."#play/uploads/";
	$YT_Video = "http://www.youtube.com/watch?v=";
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
			$post_details[$i]['post_permalink'] = $YT_Video . $YT_VideoID;			
			$post_details[$i]['Video_ID'] =$YT_VideoID;
		$i++;
		if($YT_VideoNumber == $total_posts) break;
		$YT_VideoNumber++;
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
	$j(".YCS_titulo").css({"color" : "'.($post_title_color).'"});
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
						    $atts= array(
								'video_id' => $post_details[$p]['Video_ID'],
								'width' => '600',	
								'height' => '',
								'anchor' => '',
								'default_thumb' => $default_thumb,
								'vid_type'=>'youtube'
    						);
							$anchor_replacement = wp_vid_lightbox_get_auto_thumb($atts);
							$href_content = $post_details[$p]['post_permalink'].'&amp;width='. $atts['width'].'&amp;height='.$atts['height'];
							if ($lightbox==1) {
								$rel="rel='wp-video-lightbox'";
							} else {
								$rel="target='_blank'";
							}
    						$outputLB = '<a '.$rel.' href="'.$href_content.'" title="">'.$anchor_replacement.'</a><p class="YCS_titulo">'.$post_details[$p]['post_title'].'</p>';

							$output .= '<div class="col wpvl_auto_thumb_box_wrapper wpvl_auto_thumb_box"><div id="YCS_post-title" class="post-title">'.$outputLB.'</div></div>';					
							
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


function wp_vid_lightbox_get_auto_thumb($atts)
{
    $video_id = $atts['video_id'];
	$default_thumb = $atts['default_thumb'];
    //$pieces = explode("&", $video_id);
    //$video_id = $pieces[0];

    $anchor_replacement = "";
    if($atts['vid_type']=="youtube")
    {
        //$anchor_replacement = '<div class="wpvl_auto_thumb_box_wrapper"><div class="wpvl_auto_thumb_box">';
        $anchor_replacement .= '<img src="https://img.youtube.com/vi/'.$video_id.'/'.$default_thumb.'" class="video_lightbox_auto_anchor_image" alt="" />';
        //$anchor_replacement .= '<div class="wpvl_auto_thumb_play"><img src="'.YCS_URL.'/images/play.png" class="wpvl_playbutton" /></div>';
        //$anchor_replacement .= '</div></div>';
    }
    else if($atts['vid_type']=="vimeo")
    {
        $VideoInfo = wp_vid_lightbox_getVimeoInfo($video_id);
        $thumb = $VideoInfo['thumbnail_medium'];
        //print_r($VideoInfo);
        $anchor_replacement = '<div class="wpvl_auto_thumb_box_wrapper"><div class="wpvl_auto_thumb_box">';
        $anchor_replacement .= '<img src="'.$thumb.'" class="video_lightbox_auto_anchor_image" alt="" />';
        $anchor_replacement .= '<div class="wpvl_auto_thumb_play"><img src="'.YCS_URL.'/images/play.png" class="wpvl_playbutton" /></div>';
        $anchor_replacement .= '</div></div>';
    }
    else
    {
        wp_die("<p>no video type specified</p>");
    }
    return $anchor_replacement; 
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
