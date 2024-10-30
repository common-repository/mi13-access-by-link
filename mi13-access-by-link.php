<?php
/*
Plugin Name: mi13-access-by-link
Plugin URI:  https://wordpress.org/plugins/mi13-access-by-link/
Description: Access to your posts (pending) by link.
Version:     1.2
Author:      mi13
License:     GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

*/

if( !defined( 'ABSPATH')) exit();

function mi13_access_by_link_load_languages() {
	load_plugin_textdomain( 'mi13-access-by-link', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'mi13_access_by_link_load_languages' );

function mi13_access_by_link_install() {
	mi13_access_by_link_load_languages();
	$html = 
'<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<article>
			<div class="post-thumbnail">
				$thumbnail
			</div><!-- .post-thumbnail -->
			<header class="entry-header">
				<h1 class="entry-title">$title</h1>
			</header><!-- .entry-header -->
			<div class="entry-content">
				$content
			</div><!-- .entry-content -->
			<footer class="entry-footer">
				$cat
			</footer><!-- .entry-footer -->
		</article><!-- #post-## -->
	</main><!-- #main -->
</div><!-- #primary -->';
	$default_settings = [
		'header' => '1',
		'html' => $html,
		'footer' => '1',
		'filters' => 'the_content,do_shortcode',
		'key' => '0',
		'publish' => '0'
		];
	add_option('mi13_access_by_link', $default_settings);
}
register_activation_hook(__FILE__,'mi13_access_by_link_install');

function mi13_access_by_link_deactivate() {
	unregister_setting('mi13_access_by_link', 'mi13_access_by_link');
	delete_option('mi13_access_by_link');
	$args = array('posts_per_page' => -1,'post_status' => 'pending');
	$pending_posts = get_posts($args);
	foreach( $pending_posts as $post ) {
		$id = $post->ID;
		delete_post_meta( $id, 'mi13-access-by-link-key' );
	}
}
register_deactivation_hook(__FILE__, 'mi13_access_by_link_deactivate');

function mi13_access_by_link_publish($id) {
	$post = get_post( $id );
	if(current_user_can('author') && get_option('mi13_access_by_link')['publish'] ) {
		$post->post_status = 'pending';
		wp_update_post($post);
		wp_die('<p>'.__('Administrator can publish posts only.','mi13-access-by-link').'</p><p><a href="' . admin_url('post.php?post=' . 
        $id . '&action=edit') . '">'.__('Please, come back','mi13-access-by-link').'</a></p>');
	} else delete_post_meta( $id, 'mi13-access-by-link-key' );
}
add_action ( 'publish_post', 'mi13_access_by_link_publish' );

function mi13_access_by_link_meta_box() {	
	add_meta_box('mi13_access_by_link', 'Private link', 'mi13_access_by_link_meta_box_callback', 'post', 'side', 'default');  
}
add_action('add_meta_boxes', 'mi13_access_by_link_meta_box'); 

function mi13_access_by_link_link($id,$key_on) {
	$key = '';
	if($key_on=='1') {
		$key = get_post_meta($id,'mi13-access-by-link-key',true);
		if(empty($key)) {
			$key = wp_generate_password( 12, false );
			add_post_meta( $id, 'mi13-access-by-link-key', $key, true );
		}
		$key = '&key='.$key;
	} else delete_post_meta( $id, 'mi13-access-by-link-key' );
	$link = get_admin_url(null, 'admin-ajax.php') . '?action=mi13_access_by_link&id='.$id.$key;
	return $link;		
}

function mi13_access_by_link_meta_box_callback($post) {
	$text = '';
	if($post->post_status == 'pending') {	 
		$id = $post->ID;
		$key_on = get_option('mi13_access_by_link')['key'];
		$link = mi13_access_by_link_link($id,$key_on);
		$text .= '<a href="'.$link.'" target="blank">test for '.$id.'</a>';
	} else $text .= __('for posts with the status of pending','mi13-access-by-link');
	echo '<p>'.$text.'</p>';
} 

function mi13_access_by_link_menu() {
	$page = add_options_page('mi13 access by link', 'mi13 access by link', 'manage_options', 'mi13_access_by_link', 'mi13_access_by_link_page');
}
add_action('admin_menu', 'mi13_access_by_link_menu');

function mi13_access_by_link_valid($settings) {
	$html = 
'<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<article>
			<div class="post-thumbnail">
				$thumbnail
			</div><!-- .post-thumbnail -->
			<header class="entry-header">
				<h1 class="entry-title">$title</h1>
			</header><!-- .entry-header -->
			<div class="entry-content">
				$content
			</div><!-- .entry-content -->
			<footer class="entry-footer">
				$cat
			</footer><!-- .entry-footer -->
		</article><!-- #post-## -->
	</main><!-- #main -->
</div><!-- #primary -->';
	$settings['header'] = isset($settings['header']) ? intval($settings['header']) : 0;
	$settings['footer'] = isset($settings['footer']) ? intval($settings['footer']) : 0;
	$settings['filters'] = wp_strip_all_tags($settings['filters']);
	$settings['html'] = isset($settings['html']) ? force_balance_tags( $settings['html'] ) : $html;
	$settings['key'] = isset($settings['key']) ? intval($settings['key']) : 0;
	$settings['publish'] = isset($settings['publish']) ? intval($settings['publish']) : 0;
	return $settings;
}

function mi13_access_by_link_init() {
	register_setting( 'mi13_access_by_link', 'mi13_access_by_link', 'mi13_access_by_link_valid' );
}
add_action('admin_init', 'mi13_access_by_link_init');

function mi13_access_by_link_table($key_on='0') {
	$args = array('posts_per_page' => -1,'post_status' => 'pending');
	$pending_posts = get_posts($args);
	if($pending_posts) {
?>
	<div class="tabs__content">
		<div style="color:#fff;background:green;padding:8px">
			<span><?php _e('Your posts (pending) and private links for them','mi13-access-by-link'); ?></span>
		</div>
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col">id</th>
					<th scope="col">title</th>
					<th scope="col">link</th>
				</tr>
			</thead>
			<tbody id="the-list">
<?php

	$alternate = "class='alternate'";
	
	foreach( $pending_posts as $post ) {
		$id = $post->ID;
		$link = mi13_access_by_link_link($id,$key_on);
		$test = '<a href="'.$link.'" target="blank">test for '.$id.'</a>';
		echo '<tr '.$alternate.'>
		 <td class="column-name">'.$id.'</td>
		 <td class="column-name">'.$post->post_title.'</td>
		 <td class="column-name">'.$link.'</td>
		 <td class="column-name">'.$test.'</td>
		</tr>';
		$alternate = (empty($alternate)) ? "class='alternate'" : "";
	}

?>
			</tbody>
		</table>
	</div>
<?php
	}
}

function mi13_access_by_link_page() {
	$settings = get_option('mi13_access_by_link');
	$publish = isset($settings['publish']) ? intval($settings['publish']) : 0;
	?>
    <div class="wrap">
		<h2><?php echo get_admin_page_title(); ?></h2>
		<div style="margin-top:16px;color:#fff;background:green;padding:8px"><span><?php _e('Access to your posts (pending) by link.','mi13-access-by-link'); ?></span></div>
		<form  method="post" action="options.php">
		  <?php settings_fields( 'mi13_access_by_link' ); ?>
	    <h2><?php _e('Settings'); ?></h2>
			<table class="form-table"style="background:#ffedc0">
				<tbody>
				  <tr>
				   <th style="padding-left:8px" scope="row">get_header:</th>
				   <td><input type="checkbox" name="mi13_access_by_link[header]" value="1" <?php checked(1,$settings['header']); ?> ></td>
				  </tr>
				  <tr>
				   <th style="padding-left:8px" scope="row"><?php _e('Filters:','mi13-access-by-link'); ?></th>
				   <td><input type="text" name="mi13_access_by_link[filters]" value="<?php echo $settings['filters']; ?>" size="50">
				   <p  class="description"><?php _e('Note: Some functions may not work here.','mi13-access-by-link'); ?></p></td>
				  </tr>
				  <tr>
				   <th style="padding-left:8px" scope="row">html:</th>
				   <td><textarea name="mi13_access_by_link[html]" rows="10" cols="50"><?php echo $settings['html']; ?></textarea>
				   <p  class="description"><?php _e('Note: Please use constants $title, $content, $cat and $thumbnail.','mi13-access-by-link'); ?></p></td>
				  </tr>
				  <tr>
				   <th style="padding-left:8px" scope="row">get_footer:</th>
				   <td><input type="checkbox" name="mi13_access_by_link[footer]" value="1" <?php checked(1,esc_attr($settings['footer'])); ?> ></td>
				  </tr>
				  <tr>
				   <th style="padding-left:8px" scope="row"><?php _e('Private key:','mi13-access-by-link'); ?></th>
				   <td><input type="checkbox" name="mi13_access_by_link[key]" value="1" <?php checked(1,esc_attr($settings['key'])); ?> >
				   <p  class="description"><?php _e('Note: The key will be deleted when the post is published.','mi13-access-by-link'); ?></p></td>
				  </tr>
				  <tr>
				   <th style="padding-left:8px" scope="row"><?php _e('Administrator can publish posts only:','mi13-access-by-link'); ?></th>
				   <td><input type="checkbox" name="mi13_access_by_link[publish]" value="1" <?php checked(1,esc_attr($publish)); ?> >
				   </td>
				  </tr>
				 </tbody>
				</table>
				<?php submit_button(); ?> 
	    </form>
		<?php 
		$filters = $settings['filters'];
	    $filters = trim($filters);
	    $filters = explode(',',$filters);
	    foreach ($filters as $filter) {
			if( !function_exists($filter) ) wp_admin_notice( $filter. ' - ' . __('This function was not found! Please, edit "filters" field.','mi13-access-by-link'),['type' => 'error']);
	    }
		$key_on = $settings['key'];
		mi13_access_by_link_table($key_on); 
		?>
	</div>
	<?php
}

function mi13_access_by_link_ajax() {
	$settings = get_option('mi13_access_by_link');
	$key = '';
	$id = '';
	if( isset($_GET['id']) ) $id .= sanitize_text_field($_GET['id']);
	if( isset($_GET['key']) ) $key .= sanitize_text_field($_GET['key']);
	if( $settings['key'] == '1' ) {
		$key1 = get_post_meta($id,'mi13-access-by-link-key',true);
		if( empty($key1) ) wp_die('Error: key_not_found');
		if( $key !== $key1 ) wp_die('Error: invalid key');
	}
	$post = get_post( $id );
	if($post) {
		if( ($post->post_status != 'pending') || ($post->post_type != 'post') || (!empty($post->post_password)) ) wp_die('Error: Access denied');          
		$content = $post->post_content;

		$title = $post->post_title;
		$cat = __('Category', 'mi13-access-by-link') . ': ' . get_the_category_list(' &raquo; ', 'multiple',$id);
		$thumbnail = get_the_post_thumbnail($id,'thumbnail');
		$html = $settings['html'];

		$filters = $settings['filters'];
		$filters = trim($filters);
		$filters = explode(',',$filters);

		foreach ($filters as $filter) {
			$content = apply_filters($filter, $content);
		}

		$html = str_replace('$title',$title,$html);
		$html = str_replace('$content',$content,$html);
		$html = str_replace('$cat',$cat,$html);
		$html = str_replace('$thumbnail',$thumbnail,$html);
		if( $settings['header']=='1' ) get_header();
		echo $html;
		if( $settings['footer']=='1' ) get_footer();
	} else wp_die('Error: invalid id');
	wp_die();	
}
add_action('wp_ajax_mi13_access_by_link', 'mi13_access_by_link_ajax');
add_action('wp_ajax_nopriv_mi13_access_by_link', 'mi13_access_by_link_ajax');

?>