<?php
/**
 * Plugin Name: Envira Gallery Archives
 * Description: Enables nice display of envira galleries in custom-post-type archive page and the home page
 * Author:      Hoday
 * Version:     0.0.0
 */
 
/**
 * 1. Refine envira custom post type registration to allow archive pages
 *    (needed for both the front page and archive page)
 * 2. Make envira custom post type archive page
 *    a. Redirect to page.php template
 *    b1. Make envira gallery thumbnails (used for both archive page and home page)
 *    b2. Intercept wordpress loop to print prettily
 *       and print archive page navigation
 * 3. Make envira custom post type single pages
 *    a. Intercept wordpress loop to print prettily
 *       and print single post page navigation
 * 4. Make envira custom post type show up on home page (Recent posts)
 *    a. Add envira custom post type to the query for the home page
 */

// 1. This is needed to modify the custom post type for the envira gallery to be able to display an archive page
function register_post_type_args_callback_envira( $args, $post_type ) {
    if ( $post_type == "envira" ) {
			$args['has_archive'] = 1; // if this is not here, a request for envira archive goes to the home page
			$args['public'] = 1;	// if this is not here, a request for envira archive goes to the home page
    }
    return $args;
}
add_filter( 'register_post_type_args', 'register_post_type_args_callback_envira', 20, 2 );

// 2. a. this is needed for redirecting custom post type archive page to use page.php template.
function template_include_callback($template) {
	if( is_main_query() && is_post_type_archive() &&  get_post_type() == 'envira') {			
		$new_template = locate_template( array( 'page.php' ) );
		if ($new_template != '') {
			$template = $new_template;
		}
	}
	return $template;		
}
add_filter( 'template_include', 'template_include_callback' );

// 2.b1.
// for making custom thumbnail for envira custoom post types
function get_envira_custom_thumbnail($id, $thumbnail_size) {
	$meta_image_ids_in_gallery = get_post_meta($id, '_eg_in_gallery', true);
	if (is_array($meta_image_ids_in_gallery) && sizeof($meta_image_ids_in_gallery) > 0 && '' != $meta_image_ids_in_gallery[0]){
		$id_of_first_img = $meta_image_ids_in_gallery[0];
		$thumbnail_html = wp_get_attachment_image( $id_of_first_img, $thumbnail_size, false, '');
	} else {
		$thumbnail_html = '';
	}
	return $thumbnail_html;
}

// 2.b1.
// for making custom thumbnail for envira custoom post types
// it works for both custom post type archive page and home page.
// thumbnail is not output for single post page though.
function my_post_thumbnail_fallback( $html, $post_id, $post_thumbnail_id, $size, $attr ) {

		if (get_post_type($post_id) == 'envira' && !is_single()) {
			ob_start();
			?><div><a href="<?php echo get_post_permalink($post_id); ?>"><?php echo get_envira_custom_thumbnail($post_id, $size); ?></a></div><?php
			$html = ob_get_contents();
			ob_end_clean();
		}
		return $html;
}
add_filter( 'post_thumbnail_html', 'my_post_thumbnail_fallback', 20, 5 );

// 2. b2. Helper function for 2. b2.
function get_envira_number_of_images($id) {
	$meta_image_ids_in_gallery = get_post_meta($id, '_eg_in_gallery', true);
	if (is_array($meta_image_ids_in_gallery) && sizeof($meta_image_ids_in_gallery) > 0 && $meta_image_ids_in_gallery[0] != ''){
		$number_images = sizeof($meta_image_ids_in_gallery);
	} else {
		$number_images = 0;
	}
	return $number_images;	
}

// 2. b2. This is needed for making the archive page of all envira galleries print in a special way
function loop_start_callback_envira($wpquery) {

	if( is_main_query() && is_post_type_archive() &&  get_post_type() == 'envira') {
			
		ob_start();

		// build html tree here, echo it out, and then empty the the_post variable.
		foreach ($wpquery->posts as $post) {
			
			?>
				<div class="envira-archive-post" style="display:flex; flex-direction:row; flex-wrap:nowrap; margin-top:0.5em; margin-bottom:0.5em;">
						<div class="envira-archive-post-thumbnail" style="margin-right:15px; flex-shrink:0;">
							<?php echo get_the_post_thumbnail($post->ID, 'thumbnail', array(
										'alt' => the_title_attribute('echo=0'),
										'title' => the_title_attribute('echo=0'),
									)
								); 
							?>

						</div>
						<div class="envira-archive-post-meta" style="flex-shrink:1;">
							<div class="envira-archive-post-title">
								<a href="<?php echo get_post_permalink($post->ID); ?>">
									<h2><?php echo $post->post_title;?></h2>		
								</a>
							</div>
							<div class="envira-archive-post-date">
								<span><?php echo date(get_option('date_format'), strtotime($post->post_date)); ?></span>
							</div>
							<div class="envira-archive-post-img-number">
								<span><?php echo get_envira_number_of_images($post->ID); ?> photos</span>
							</div>								
						</div>
				</div>
			<?php

		}
		
		?>
			<div class="navigation link-bold">
					<p><?php posts_nav_link();?></p>
			</div>		
		<?php
		
		$cummulative_post = ob_get_contents();
		ob_end_clean();
		
		$dummy_post = new stdClass();
		$dummy_post->post_title = __('Gallery');
		$dummy_post->post_content = $cummulative_post;		
		$dummy_post->ID = -99;
		$dummy_post->post_author = '';
		$dummy_post->post_date = '';
		$dummy_post->post_date_gmt = '';
		$dummy_post->post_status = 'publish';
		$dummy_post->comment_status = 'closed';
		$dummy_post->ping_status = 'closed';
		$dummy_post->post_name = 'dummy-envira-archive'; // append random number to avoid clash
		$dummy_post->post_type = 'page';
		$dummy_post->filter = 'raw'; // important
		$dummy_post_obj = new WP_Post($dummy_post);
		$wpquery->posts = array($dummy_post_obj);
		$wpquery->post_count = 1;
		
		//echo "<pre>";
		//print_r($wpquery);
		//echo "</pre>";

	}	
}
add_action( 'loop_start', 'loop_start_callback_envira', 10, 1);



// 3 a. this is needed to display shortcode for single posts page
function loop_start_callback_envira_single($wpquery) {

	if(is_main_query() && is_single() &&  get_post_type() == 'envira') {
			
			foreach ($wpquery->posts as $post) {
				$myshortcode = '[envira-gallery id="'.$post->ID.'"]';
				$post->post_content = $myshortcode;
			}		
		}
}
add_action( 'loop_start', 'loop_start_callback_envira_single', 10, 1);


		
// 4. This is needed to make envira custom post type be included on the home page (new posts)
function pre_get_posts_callback_add_envira($query) {

    if ($query->is_home() && $query->is_main_query()) {

				//$query->query_vars[post_type] = ['envira', 'post'];	
				if (array_key_exists('post_type', $query->query_vars)){
					$old_post_types = $query->query_vars['post_type'];
					if (!is_array($old_post_types)) {
						$old_post_types = array();
					}					
				} else {
					$old_post_types = array();
				}

        array_push($old_post_types, 'envira');
        array_push($old_post_types, 'post');
				$query->query_vars['post_type'] = $old_post_types;
		}	
}
add_action('pre_get_posts', 'pre_get_posts_callback_add_envira');

// This is needed to overwrite post type of envira custom post type 
// so that the post date is displayed on the home page in the 2017 theme
// actually this is not good because if the post type is overwritten,
// there is no way to know to use a differet thumbnail
/*
function the_post_callback_envira_home_page($post, $query) {
    if (is_home() && is_main_query()){
			if ($post->post_type == "envira") {
					$post->post_type = "post";
			}
		}
}
//add_action( 'the_post', 'the_post_callback_envira_home_page', 1000, 2);
*/

// This is needed for making the shortcodes display in content in envira gallery posts on front page
/*
function loop_start_callback_envira_front($wpquery) {

    if ( $wpquery->is_main_query() && $wpquery->is_home()) {
			foreach ($wpquery->posts as $post) {
				if ($post->post_type=='envira'){
					// do not put shortcode in the newest posts archves - if we have more than one envira gallery per page, the lightbx display will not work correctly.
					//$myshortcode = '[envira-gallery id="'.$post->ID.'"]';
					//$post->post_content = $myshortcode;
		
				}

			}
						
		}
			
		
}
*/
//add_action( 'loop_start', 'loop_start_callback_envira_front', 10, 1);

// this may be unneeded!
// 3. a. This is needed for making single-pages of envira galleries include the shortcode
/*
function the_post_callback_envira_print_shortcode($wp_post, $wpquery) {

	if( is_main_query() && is_single() &&  get_post_type() == 'envira') {
		
		ob_start();
		
		foreach ($wpquery->posts as $post) {
			
			?>
				<div>
					<div>
						<h1><?php echo $post->post_title;?></h1>
					</div>
					<div>
						<span><?php echo date(get_option('date_format'), strtotime($post->post_date)); ?></span>
					</div>
					<div>
						<?php 
							$myshortcode = '[envira-gallery id="'.$post->ID.'"]';
							//echo $myshortcode;
							echo do_shortcode($myshortcode); 
						?>
					</div>
				
				</div>
			<?php
		}
		$cummulative_post = ob_get_contents();
		ob_end_clean();		
		

		
		//$wpquery->posts = [];
		//$wp_post = [];
		
		unset($GLOBALS['wp_post']);

		
	}
		
}
//add_action( 'the_post', 'the_post_callback_envira_print_shortcode', 10, 2);
*/



// for making nav links on single post page point to next post regardless of post type
//  this is not adequate because although it will chane the post type of current page, this only means that
// the nav links to the next pages will be for pages in the "post" category. pages in custom post types will be skipped over/ignored.
// what i need is a way to intercept the function for creating nav links that is called in the template
// and insert my own.

/*
function loop_end_callback_envira($wpquery) {
	if (is_main_query() && is_single()) {
		$wpquery->posts[0]->post_type = 'post';
		echo "<pre>";
		print_r($wpquery);
		echo "</pre>";
		
	}
}
add_action('loop_end', loop_end_callback_envira);
*/


// COMPLETED TODO:
// why extra lines at bottom of archive page
// add handling for no images in a gallery
// figure why thumbnail is not showing on front page archives
// add back and forward llinks to single page for envira posts
// the links between single pages are not working corretly:
// i need a way to distinguish between when the links should link to next post within the custom post type, (for custom post type archive page)
// or next post within all posts. (for home page)
// right now, since these two situations are not distinguished, the custom post type arhive page is working correctly, but the home page is not.
// in the home page, there are 3 subgroups: posts, envira custom post type, and tribe events.
// 2017 theme uses the_post_navigation instead pf previous and next links.
// maybe there's a hook i can use.



