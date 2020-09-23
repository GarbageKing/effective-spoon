<?php

/*
 * Plugin Name: Daily NASA
*/
if ( ! defined( 'ABSPATH' ) ) exit;

function nasa_post_type(){
	register_post_type( 'post-nasa-gallery', [
		'label'  => 'NASA Gallery',
		'labels' => [
			'name'               => __('NASA Gallery'), 
			'singular_name'      => __('Daily Pic'), 			
		],
		'description'         => '',
		'public'              => true,		
		'show_in_menu'        => true, 				
		'hierarchical'        => false,
		'supports'            => ['title', 'editor', 'thumbnail'],
		'taxonomies'          => [],
		'has_archive'         => false,
		'rewrite'             => true,
		'query_var'           => true,
	] );
}
add_action('init', 'nasa_post_type');

function Generate_Featured_Image($file, $post_id, $desc = ''){

	require_once(ABSPATH . 'wp-admin/includes/media.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Set variables for storage, fix file filename for query strings.
    preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
    if ( ! $matches ) {
         return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
    }

    $file_array = array();
    $file_array['name'] = basename( $matches[0] );

    // Download file to temp location.
    $file_array['tmp_name'] = download_url( $file );

    // If error storing temporarily, return the error.
    if ( is_wp_error( $file_array['tmp_name'] ) ) {
        return $file_array['tmp_name'];
    }

    // Do the validation and storage stuff.
    $id = media_handle_sideload( $file_array, $post_id, $desc );

    // If error storing permanently, unlink.
    if ( is_wp_error( $id ) ) {
        @unlink( $file_array['tmp_name'] );
        return $id;
    }
    return set_post_thumbnail( $post_id, $id );

}
 
function daily_nasa_function($get_date = false) {
	if(!$get_date){
		$get_date = date('Y-m-d');
	}
    
    if(!post_exists($get_date)){ //do nothing if today's pic already added. this is useful when we de-/activate plugin the same day, so it won't create duplicates

    	$remote_get = wp_remote_get('https://api.nasa.gov/planetary/apod?api_key=xfVp3d9T9SDcCyyrqVvjbiOnlRiAPFgA36tuqGMZ&date='.$get_date);
    	$image = json_decode($remote_get['body'])->url;

	    $nasa_post = array(
		  'post_title'    => $get_date,
		  'post_content'  => '<img src="'.$image.'">',
		  'post_status'   => 'publish',
		  'post_author'   => 1,
		  'post_type' => 'post-nasa-gallery'
		);	 
		$post_id = wp_insert_post($nasa_post);
		Generate_Featured_Image($image, $post_id);
	}
}

add_action('do_daily_nasa', 'daily_nasa_function');

function enqueue_nasa() {
	wp_enqueue_script('slick', plugin_dir_url( __FILE__ ) . 'assets/slick/slick.js', array('jquery') );
	wp_enqueue_style('slick', plugin_dir_url( __FILE__ ) . 'assets/slick/slick.css');
	wp_enqueue_style('slick-theme', plugin_dir_url( __FILE__ ) . 'assets/slick/slick-theme.css');
	wp_enqueue_script('custom', plugin_dir_url( __FILE__ ) . 'assets/js/custom.js', array('jquery') );
}
add_action('wp_enqueue_scripts', 'enqueue_nasa');

//test
function my_cron_schedules($schedules){
    if(!isset($schedules["1min"])){
        $schedules["1min"] = array(
            'interval' => 60,
            'display' => __('Once every minute'));
    }    
    return $schedules;
}
add_filter('cron_schedules','my_cron_schedules');
//test

function starting_pics()
{	
	$hasposts = get_posts(['post_type' => 'post-nasa-gallery']); //if no posts of this type, add a few for testing purpose
	if(!$hasposts){ //no need to insert 5 if it's not a first activation (there are posts of this type already)
		for($i=4; $i>=0; $i--){
			$date_of_interest = date('Y-m-d', strtotime('-'.$i.' days'));
			daily_nasa_function($date_of_interest);
		} 
	}
	
	if (!wp_next_scheduled('do_daily_nasa')) {
		if(!$hasposts){		
	    	wp_schedule_event(strtotime(date('Y-m-d H:i:s', strtotime('+1 day'))), 'daily', 'do_daily_nasa'); //daily, starting from tomorrow if we added a few test ones, including today's already
		} else {
			wp_schedule_event(time(), 'daily', 'do_daily_nasa'); //if we didn't create todays post yet, we'll start from today
		}
	}		

}
register_activation_hook(__FILE__, 'starting_pics');


function nasa_end() {
    wp_clear_scheduled_hook('do_daily_nasa');
}
register_deactivation_hook(__FILE__, 'nasa_end'); 

function nasa_display_latest() { 
	
    $recent_posts = wp_get_recent_posts(array(
        'numberposts' => 5,
        'post_type' => 'post-nasa-gallery'
    )); ?>
    <h3 style="text-align: center">Latest NASA daily photos</h3>
    <div class="nasa-gallery-items">
    <?php foreach($recent_posts as $post) : ?>
        <div>
            <a href="<?php echo get_permalink($post['ID']) ?>">
                <?php echo get_the_post_thumbnail($post['ID'], array(500,300)); ?>                
            </a>
        </div>
    <?php endforeach; wp_reset_query(); ?>
	</div>

<?php }
add_shortcode('nasa_latest', 'nasa_display_latest');