<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Add filter that inserts our new tab
function canto_media_menu( $tabs ) {
	$newtab = array( 'canto' => __( 'Canto', 'canto' ) );

	return array_merge( $tabs, $newtab );
}

// Load media_nsm_process() into the existing iframe
function canto_media_upload_canto() {
	$nsm = new canto_media();

	return wp_iframe( array( $nsm, 'media_upload_canto' ), array() );
}

function canto_media_init() {
	if ( current_user_can( 'upload_files' ) ) {
		load_plugin_textdomain( 'canto', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_filter( 'media_upload_tabs', 'canto_media_menu' );
		add_action( 'media_upload_canto', 'canto_media_upload_canto' );
		add_action( 'media_upload_canto', 'get_meta_data' );
	}
}

add_action( 'init', 'canto_media_init' );

class canto_media {
	var $media_items = '';

	/**
	 * @param unknown_type $errors
	 */
	function media_upload_canto( $errors ) {
		global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types, $blog_id;

		media_upload_header();


		// set the first part of the form action url now, to the current active site, to prevent X-Frame-Options problems
		$post_id = intval( $_REQUEST['post_id'] );
		$form_action_url = plugins_url( 'copy-media.php', __FILE__ );
		$form_action_url .= "?type=$type&tab=library&post_id=$post_id";
		$form_action_url = apply_filters( 'media_upload_form_url', $form_action_url, $type );
		$form_class = 'media-upload-form validate';

		?>


<style>
#fbc-react .content .col-md-2 { height: 130px; }
</style>




		<?php

		if( get_option( 'fbc_app_expire_token' ) < time() || (get_option( 'fbc_flight_domain' )  == '' || get_option( 'fbc_app_token' ) == '') ) {

			if( get_option( 'fbc_app_expire_token' ) < time() ) {
				echo '<form><h3 class="media-title"><span style="font-size:14px;font-family:Helvetica,Arial">' . __( "<strong>Oops!</strong> Your security token has expired Please re-authenticate your Canto account. <a href=\"javascript:;\" onclick=\"window.top.location.href='" . get_bloginfo( 'url' ) . "/wp-admin/options-general.php?page=canto_settings'\">Plugin Settings</a>",
						'canto' ) . '</span></h3></form>';
			}

			if ( get_option( 'fbc_flight_domain' )  == '' || get_option( 'fbc_app_token' ) == '' ) {
				echo '<form><h3 class="media-title"><span style="font-size:14px;font-family:Helvetica,Arial">' . __( "<strong>Oops!</strong> You haven't connected your Canto account yet. <a href=\"javascript:;\" onclick=\"window.top.location.href='" . get_bloginfo( 'url' ) . "/wp-admin/options-general.php?page=canto_settings'\">Plugin Settings</a>",
						'canto' ) . '</span></h3></form>';

			}


		} else {
			?>
			<img src="<?php echo FBC_URL; ?>/assets/loader_white.gif" id="loader">
			<link rel="stylesheet" type="" href="<?php echo FBC_URL; ?>public/assets/app.styles.css">

					<section id="root"></section>


			<?php

			//echo esc_attr( $form_action_url );

			$app_api = (get_option('fbc_app_api')) ? get_option('fbc_app_api') : 'canto.com';

			$translation_array = array(
				'FBC_URL' 	=> FBC_URL,
				'FBC_PATH' 	=> FBC_PATH,
				'app_api'		=> $app_api,
				'subdomain' => get_option( 'fbc_flight_domain' ),
				'token'		=> get_option( 'fbc_app_token' ),
				'action'	=> esc_attr( $form_action_url ),
				'abspath'	=> urlencode(ABSPATH),
				'postID'	=> $post_id,
				'limit'		=> 30,
				'start'		=> 0
			);


			wp_register_script( 'fbc-react-vendor', FBC_URL.'public/assets/app.vendor.bundle.js',array(),false,true);
			wp_register_script( 'fbc-react-bundle', FBC_URL.'public/assets/app.bundle.js',array(),false,true);

			wp_localize_script( 'fbc-react-vendor', 'args', $translation_array );
			wp_localize_script( 'fbc-react-bundle', 'args', $translation_array );

			wp_enqueue_script ( 'fbc-react-vendor' );
			wp_enqueue_script ( 'fbc-react-bundle' );
			?>
					<!--div id="fbc-react"></div-->

		<?php
	}
		//Stop checking to see if user has valid flight credentials


		$_GET['paged'] = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0;
		if ( $_GET['paged'] < 1 ) {
			$_GET['paged'] = 1;
		}
		$start = ( $_GET['paged'] - 1 ) * 10;
		if ( $start < 1 ) {
			$start = 0;
		}
		//add_filter( 'post_limits', create_function( '$a', "return 'LIMIT $start, 10';" ) );

		list( $post_mime_types, $avail_post_mime_types ) = wp_edit_attachments_query();

	}
}

?>
<?php

function get_image_sizes( $size = '' ) {

	global $_wp_additional_image_sizes;

	$sizes                        = array();
	$get_intermediate_image_sizes = get_intermediate_image_sizes();

	// Create the full array with sizes and crop info
	foreach ( $get_intermediate_image_sizes as $_size ) {

		if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {

			$sizes[ $_size ]['width']  = get_option( $_size . '_size_w' );
			$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
			$sizes[ $_size ]['crop']   = (bool) get_option( $_size . '_crop' );

		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {

			$sizes[ $_size ] = array(
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop']
			);

		}

	}

	// Get only 1 size if found
	if ( $size ) {
		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		} else {
			return false;
		}
	}

	return $sizes;
}


function fbc_insert_custom_image_sizes( $sizes ) {
	global $_wp_additional_image_sizes;
	if ( empty( $_wp_additional_image_sizes ) ) {
		return $sizes;
	}

	foreach ( $_wp_additional_image_sizes as $id => $data ) {
		if ( ! isset( $sizes[ $id ] ) ) {
			$sizes[ $id ] = ucfirst( str_replace( '-', ' ', $id ) );
		}
	}

	return $sizes;
}

//Add custom image sized to thumbnail selection if the user hasnt already.
add_filter( 'image_size_names_choose', 'fbc_insert_custom_image_sizes' );

if ( ! has_filter( 'image_size_names_choose' ) ) {
	add_filter( 'image_size_names_choose', 'fbc_insert_custom_image_sizes' );
}



function get_meta_data() {

	$nonce = wp_create_nonce( 'canto' );
	return;
}
