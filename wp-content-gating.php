<?php
/**
 * Adds content gating to WordPress
 * @package WordPress
 * 
 * Plugin Name: WP Content Gating
 * Plugin URI: http://example.com/
 * Description: Add gating to your content. Have users submit contact info before filling downloading or viewing content
 * Version: 1.0
 * Author: De"Yonte W.
 * Author URI: http://example.com/
*/

/**
 * Copyright (c) 2013 De"Yonte W. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */
// File Security Check
if ( ! empty( $_SERVER['SCRIPT_FILENAME'] ) && basename( __FILE__ ) == basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
    die ( 'You do not have sufficient permissions to access this page!' );
}
	
/**
 * Class to provide content gating
 * @subpackage WordPress
 * @category Plugin   
 * @author De'Yonte W.
 */
class WPContentGate{
	
	public $before_content;
	public $after_content;

	/**
	 * create the constructor for the class
	 */
	public function __construct(){
		if( is_admin() ){
			//load the hooks for the dashboard section of the site
			$this->admin_hooks();
		}
		else{
			//load the hooks for the front of the site/non-dashboard section of the site
			$this->front_hooks();
		}

		//always perform functions
		add_action('init',array($this,'define_post_type'));
		add_filter( 'template_include', array($this,'page_template'));
		add_filter( 'single_template', array($this,'page_template'));
	}

	/*================START ADMIN SECTION================*/

	/**
	 * load the WordPress hooks for the admin/backend side of WordPress
	 */
	public function admin_hooks(){
		
		add_action('media_buttons_context',array($this,'tiny_mce_buttons'));
		add_action('admin_footer',array($this,'gating_popup' ));
		add_action('admin_enqueue_scripts',array($this,'admin_scripts'));
		add_action('wp_ajax_wcg_preview_popup',array($this,'preview_popup_callback'));
		//include admin styles
		$this->admin_styles();
	}

	/**
	 *add content gate custom post type
	 */
	public function define_post_type(){
		$settings = array(
			'labels'=>array(
				'name'=> 'Content Gates',
				'singular_name'=> 'Content Gate',
				'add_new'=> 'Add New',
				'edit'=> 'Edit',
				'edit_item'=> 'Edit Content Gate',
				'new_item'=> 'New Content Gate',
				'view' => 'View',
				'view_item'=> 'View Content Gate',
				'search_items'=> 'Search Content Gate',
				'not_found'=> 'No Content Gate Found',
				'not_found_in_trash'=> 'No Content Gate found in Trash',
				'parent'=> 'Parent Content Gate'
			),
			'public'=> true,
			'publicly_queryable'=>true,
			'has_archive'=>true,
			'supports'=> array('title','editor','thumbnail','custom-fields'),
			'has_archive'=> true,
			'rewrite' => array('slug'=>'wcg'),
			'show_ui'=>true,
			'capability_type'=>'post'
		);
		register_post_type( 'content-gate', $settings );
		flush_rewrite_rules( false );
	}
	
	/**
	 * add content gating button to tiny mce editor
	 * 
	 * add new button to tiny mce editor in order to add
	 */
	public function tiny_mce_buttons( $context ) {
		//return $context.="<a href='#TB_inline?width=480&height=480&inlineId=wp_cg_popup' class='button thickbox' id='mytestpage2' title='Add Content Gate'>Content Gating</a>";
    return $context.="<a class='button' id='add_wcg_gate' title='Add Content Gate'>Content Gating</a>";
	}

	/**
	 * add field titles for the custom field types
	 * 
	 * add the field titles for each of the custom field types. This appears when you hover over the field on the backend of the Gravity Forms editor page
	 * @param string $type 
	 * @return string the title of the field
	 */
	public function gating_popup(){
		global $post;
		?>
		<div id="wp_cg_popup">
			<div style="padding:15px 15px 0 15px;" id="wcg_steps">
				<h1>Gate This Content...</h1>
				<div class="wp_cg_add_new">
					<input type="text" id="wp_cg_add_new" placeholder="URL to Content Gate"><button class="button-primary" id="wcg_url" data-yet="js">Go to Step 2</button>
				</div>
				<h1>My Popup will look like...</h1>
				<div class="before_content">
					<button class="button-primary" id="wcg_preview">Preview Popup</button>
					<?php wp_editor('','wgc_content');?>
				</div>
			</div>
		</div>
		<script>jQuery(document).ready(function(){
      var wcg_post_id = null;
			jQuery(document).on('click','button#wcg_preview',function(e){
				e.preventDefault();
				jQuery.ajax({
					type: "post",
					url: "<?php echo admin_url('admin-ajax.php');?>",
					cache: false,
					data: { 
						action: 'wcg_preview_popup', 
						post_id: <?php echo $_GET['post'];?>,
						wcg_content: tinyMCE.get('wgc_content').getContent(),
						wcg_post_id: wcg_post_id,
            _ajax_nonce: '<?php echo wp_create_nonce( "wcg" ) ?>' 
					},
					dataType: 'html',
					success: function(html,textStatus,jqXHR){
						console.log(html);
            wcg_post_id = html;
					},
					error: function(jqXHR,textStatus,errorThrown ){
					}
				});
			});
		});
		</script>

	<?php
	}

	/**
	 * add scripts to dashboard
	 */
	public function admin_scripts(){	
		wp_enqueue_script( 'jquery-steps',plugins_url( '/assets/admin/js/jquery.steps/jquery.steps.js', __FILE__ ),array('jquery'),'1.0.4' );
		wp_enqueue_script( 'boostrap-tabs-js', plugins_url( '/assets/admin/js/bootstrap.tabs/bootstrap.tabs.js', __FILE__ ), array('jquery'), '3.0.3' );
		wp_enqueue_script( 'wgc-scripts',plugins_url( '/assets/admin/js/wcg-scripts.js', __FILE__ ),array('jquery'),'1.0',true );
	}

	public function admin_styles(){
		wp_enqueue_style( 'boostrap-tabs-css', plugins_url( '/assets/admin/js/bootstrap.tabs/bootstrap.tabs.css', __FILE__ ), '', '3.0.3' );
	}

	/**
	 * recursive in_array function
	 * 
	 * recursively search multidimensional arrays
	 * @link http://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
	 * @return boolean if element is found or not found
	 */
	public function in_array_r($needle, $haystack, $strict = false){
    foreach ($haystack as $item) {
      if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
          return true;
      }
    }

		return false;
	}

	/**
	 * preview popup function
	 * 
	 * ajax callback function to preview the popup in the admin section
	 */
	public function preview_popup_callback(){
		global $wpdb; // this is how you get access to the database
		//check_ajax_referer( "socpop" );
		$status = null;
		//grab the socpop id from the ajax call
		$post_id = (int)$_POST['post_id'];
		$wcg_content = $_POST['wcg_content'];
    $wcg_post_id = $_POST['wcg_post_id'];
		if( empty($wcg_post_id) ){
			$post = array(
				'post_content'=> $wcg_content,
				'post_name'=> 'this-post',
				'post_title'=> 'this title',
        'post_type'=> 'content-gate',
			);
      $wcg_post_id = wp_insert_post( $post, $wp_error = false );
		  wp_publish_post( $wcg_post_id );
    }
    
		update_post_meta($post_id,'wcg_popup',$wcg_post_id);
		echo $wcg_post_id;
		die();
	}

	/*================END ADMIN SECTION================*/

	/**
	 * load the WordPress hooks for the front side of WordPress
	 */
	public function front_hooks(){
		add_action( 'wp_enqueue_scripts', array($this,'front_scripts') );
		if( !is_admin() )
			$this->front_styles();
	}

	public function front_styles(){
		wp_enqueue_style( 'bootstrap-modal-css', plugins_url("assets/front/css/bootstrap.modal.css",__FILE__), "3.0.3" );
	}

	public function front_scripts(){
		wp_enqueue_script( 'bootstrap-modal-js', plugins_url("assets/front/js/bootstrap.modal.js",__FILE__), array('jquery'), "3.0.3" );
	}

	public function page_template($template_path){
		// Post ID
		global $wp_query, $post, $posts;
		if( get_post_type() == "content-gate" ){

			if( is_single() ){
				$template_path = plugin_dir_path( __FILE__ ) . '/assets/front/templates/single-content-gate.php';
			}
		}

		return $template_path;
	}

}

$wp_content_gate = new WPContentGate();