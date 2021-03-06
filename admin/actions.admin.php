<?php
/**
 * actions.admin.php
 */

// *********************************************
// MOVE USERS TO FRONT END EDITOR
// *********************************************

define( 'PL_ACTIVATE_URL' , apply_filters('pl_activate_url', home_url().'?tablink=account&tabsublink=welcome'));

global $pagenow;
if( is_admin() && isset($_GET['activated'] ) && $pagenow == "themes.php" )
	wp_redirect( PL_ACTIVATE_URL );


add_action( 'admin_init', 'pagelines_check_customizer' );
function pagelines_check_customizer() {
	
	global $pagenow;
	
	if($pagenow == 'customize.php')
		wp_redirect( PL_ACTIVATE_URL );
}


/**
 * Add Javascript for Layout Controls from the Layout UI class
 *
 */
$layout_control_js = new PageLinesLayoutControl();
add_action( 'pagelines_admin_head', array(&$layout_control_js, 'layout_control_javascript' ) );


/**
 * Admin Body Class
 *
 * Adds the 'pagelines_ui' class
 *
 * @package PageLines DMS
 * @since   ...
 *
 * @param   $class
 *
 * @return  string
 */
function pagelines_admin_body_class( $class ){

	$class = $class.'pagelines_ui';

	return $class;
}
/**
 * Checks if PHP5
 *
 * Tests for installed version of PHP higher than 5.0 and prints message if version is found to be lower.
 *
 * @package PageLines DMS
 * @subpackage Functions Library
 * @since 4.0.0
 */
add_action( 'pagelines_before_optionUI', 'pagelines_check_php' );
function pagelines_check_php(){
	if( floatval( phpversion() ) < 5.0 ){
		printf( __( "<div class='config-error'><h2>PHP Version Problem</h2>Looks like you are using PHP version: <strong>%s</strong>. To run this framework you will need PHP <strong>5.0</strong> or better...<br/><br/> Don't worry though! Just check with your host about a quick upgrade.</div>", 'pagelines' ), phpversion() );
	}
}


/**
 * Ajax Callback
 *
 * AJAX OPTION SAVING
 * Used to save via AJAX theme options and image uploads
 *
 * @package PageLines DMS
 * @since 1.2.0
 */
add_action( 'wp_ajax_pagelines_ajax_post_action', 'pagelines_ajax_callback' );
function pagelines_ajax_callback() {
    /** This is how you get access to the database */
	global $wpdb;

	$save_type = ( $_POST['type'] ) ? $_POST['type'] : null;

	$setting = $_POST['setting'];
	$button_id = $_POST['oid'];

	$pieces = explode( 'OID', $_POST['oid'] );
	$oid = $pieces[0];
	$parent_oid = ( isset($pieces[1]) ) ? $pieces[1] : null;

	// Uploads
	if( $save_type == 'upload' ) {


		$arr_file_type = wp_check_filetype( basename( $_FILES[$button_id]['name'] ));

		$uploaded_file_type = $arr_file_type['type'];

		// Set an array containing a list of acceptable formats
		$allowed_file_types = array( 'image/jpg','image/jpeg','image/gif','image/png', 'image/x-icon');

		if( in_array( $uploaded_file_type, $allowed_file_types ) ) {

			$filename = $_FILES[ $button_id ];
			$filename['name'] = preg_replace( '/[^a-zA-Z0-9._\-]/', '', $filename['name'] );

			$override['test_form'] = false;
			$override['action'] = 'wp_handle_upload';

			$uploaded_file = wp_handle_upload( $filename, $override );

			$upload_tracking[] = $button_id;

			plupop( $oid, $uploaded_file['url'], array( 'setting' => $setting, 'parent' => $parent_oid ) );

			$name = 'PageLines- ' . addslashes( $filename['name'] );

			$attachment = array(
							'guid' => $uploaded_file['url'],
							'post_mime_type'	=> $uploaded_file_type,
							'post_title'		=> $name,
							'post_content'		=> '',
							'post_status'		=> 'inherit'
						);

			$attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
			wp_update_attachment_metadata( $attach_id,  $attach_data );

		} else
			$uploaded_file['error'] = __( 'Unsupported file type!', 'pagelines' );

		if( !empty( $uploaded_file['error'] ) )
			echo sprintf( __('Upload Error: %s', 'pagelines' ) , $uploaded_file['error'] );
		else{
			//print_r($r);
			echo $uploaded_file['url']; // Is the Response

		}
	} elseif( $save_type == 'image_reset' ){
		plupop( $oid, null, array( 'setting' => $setting, 'parent' => $parent_oid ) );
	}

	die();
}

/**
 * (AJAX) Save Template Map
 *
 * Used to save via AJAX theme options and image uploads
 *
 * @package PageLines DMS
 * @since   ...
 *
 * @uses save_tempalte_map
 */
add_action( 'wp_ajax_pagelines_save_sortable', 'ajax_save_template_map' );
function ajax_save_template_map() {
    /** This is how you get access to the database */
    global $wpdb;

    /** Full Template Map */
    $templatemap = get_option( PAGELINES_TEMPLATE_MAP );

    /** Order of the sections */
    $section_order =  $_GET['orderdata'];

    /** Get array / variable format */
    parse_str( $section_order );

    /** Selected Template */
    $selected_template = esc_attr( $_GET['template'] );

    /** Explode by hyphen to get heirarchy */
    $template_heirarchy = explode( '-', $selected_template );

    if( isset($template_heirarchy[1]) )
        $templatemap[$template_heirarchy[0]]['templates'][$template_heirarchy[1]]['sections'] = urlencode_deep( $section );
    else
        $templatemap[$selected_template]['sections'] = $section;

    save_template_map( $templatemap );

	PageLinesRenderCSS::flush_version( false );

    echo true;

    die();
}

/**
 * Ajax Save Options Callback
 *
 * @package PageLines DMS
 * @since   ...
 *
 */
add_action( 'wp_ajax_pagelines_ajax_save_option', 'pagelines_ajax_save_option_callback' );
function pagelines_ajax_save_option_callback() {
    /** This is how you get access to the database */
	global $wpdb;

	$option_name = $_POST['option_name'];
	$option_value = $_POST['option_value'];

	update_option( $option_name, $option_value );

	die();
}

/**
 * Inline Help
 *
 * Load Inline help system.
 *
 * @package PageLines DMS
 * @since   ...
 *
 * @uses    PageLines_Inline_Help
 */
add_action( 'admin_init', 'pagelines_inline_help' );
function pagelines_inline_help() {

	$pl_help = new PageLines_Inline_Help;
}

/**
 * Page Columns
 *
 * Add custom columns to page/post views.
 *
 * @package PageLines DMS
 * @since   2.1.3
 *
 * @param   $columns
 * @return  array
 */
add_filter('manage_edit-page_columns', 'pl_page_columns');
function pl_page_columns($columns) {

    	$columns['template'] = 'PageLines Template';

	return $columns;
}

/**
 * Post Columns
 *
 * @package PageLines DMS
 * @since   2.1.3
 *
 * @param   $columns
 * @return  array
 */
add_filter('manage_edit-post_columns', 'pl_post_columns');
function pl_post_columns($columns) {

    	$columns['feature'] = 'Featured Image';
	return $columns;
}

/**
 * Posts Show Columns
 *
 * @package PageLines DMS
 * @since   ...
 *
 * @param   $name
 *
 */
add_action('manage_posts_custom_column',  'pl_posts_show_columns');
function pl_posts_show_columns($name) {
    global $post;
    switch ($name) {

		case 'feature':
			if( has_post_thumbnail( $post->ID )) {
				the_post_thumbnail( array(48,48) );
			}

		break;
    }
}

/**
 * Page Show Columns
 *
 * @package PageLines DMS
 * @since   2.1.3
 *
 * @param   $name
 *
 * @uses    pl_file_get_contents
 */
add_action( 'manage_pages_custom_column', 'pl_page_show_columns' );
function pl_page_show_columns($name) {
    global $post;
    switch ($name) {
        case 'template':
            $template = get_post_meta( $post->ID, '_wp_page_template', true );

			if ( 'default' == $template ) {
				_e( 'Default', 'pagelines' );
				break;
			}

			$file = sprintf( '%s/%s', PL_PARENT_DIR, $template );

			if ( !is_file( $file ) )
				$file = sprintf( '%s/%s', CHILD_DIR, $template );

			if ( !is_file( $file ) ) {
				printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%s&action=edit', $post->ID ) ), __( 'No Template Assigned', 'pagelines' ) ) ;
				break;
			}

			$data = get_file_data( $file, array( 'name' => 'Template Name' ) );

			if ( is_array( $data ) && isset( $data['name'] ) )
				$template = $data['name'];
			else
				$template = __( 'Default', 'pagelines' );

			echo $template;
		break;

		case 'feature':
			if( has_post_thumbnail( $post->ID )) {
				the_post_thumbnail( array(48,48) );
			}

		break;
    }
}

/**
 * Setup Versions and flush caches.
 *
 * @package PageLines DMS
 * @since   2.2
 */
add_action( 'admin_init', 'pagelines_set_versions' );
function pagelines_set_versions() {
	if ( current_user_can( 'edit_themes' ) ) {
		if( defined( 'PL_LESS_DEV' ) && PL_LESS_DEV ) {
			PageLinesRenderCSS::flush_version( false );
			delete_transient( 'pagelines_sections_cache' );
		}
	}
	set_theme_mod( 'pagelines_version', pl_get_theme_data( get_template_directory(), 'Version' ) );
	set_theme_mod( 'pagelines_child_version', pl_get_theme_data( get_stylesheet_directory(), 'Version' ) );
}

// make sure were running out of 'pagelines' folder.
add_action( 'admin_notices', 'pagelines_check_folders' );
function pagelines_check_folders() {

		$folder = basename( get_template_directory() );

		if( 'dms' == $folder )
			return;

		echo '<div class="updated">';
		printf( "<p><h3>Install Error!</h3><br />PageLines DMS must be installed in a folder called 'dms' to work with child themes and extensions.<br /><br />Current path: %s<br /></p>", get_template_directory() );
		echo '</div>';
}

add_action( 'activate_plugin', 'pagelines_purge_sections_cache' );
add_action( 'deactivate_plugin', 'pagelines_purge_sections_cache' );
function pagelines_purge_sections_cache() {
	delete_transient( 'pagelines_sections_cache' );
}