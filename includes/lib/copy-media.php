<?php
/**
 * @package Canto
 * @version 2.0.0
 */

define( 'WP_ADMIN', false );
define( 'WP_LOAD_IMPORTERS', false );


function curl_action( $url, $echo ) {

	if ( ! function_exists( 'curl_init' ) ) {
		die( 'Sorry cURL is not installed!' );
	}

	$header = array( 'Authorization: Bearer ' . $_POST['fbc_app_token'] );

	$ch = curl_init();

	$options = array(
		CURLOPT_URL            => $url,
		CURLOPT_REFERER        => "Wordpress Plugin",
		CURLOPT_USERAGENT      => "WordPress Plugin",
		CURLOPT_HTTPHEADER     => $header,
		//CURLOPT_SSLVERSION     => 3,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_HEADER         => $echo,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_TIMEOUT        => 10,
	);

	curl_setopt_array( $ch, $options );
	$output = curl_exec( $ch );
	curl_close( $ch );

	//return curl_error($ch);
	return $output;
}

//require_once( dirname( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) ) . '/wp-admin/admin.php' );

require_once ( urldecode($_POST["abspath"]) . 'wp-admin/admin.php' );

if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( $_POST["abspath"] . 'wp-admin/includes/file.php' );
}

if ( isset( $_POST['post_id'] ) ) {
	$send_id = (int)$_POST['post_id'];
}


//if ( isset( $send_id ) ) {
global $post;

$attachment = $_POST['fbc_id'];
$id         = $send_id;

//Go get the media item from Flight
$flight['api_url']  = 'https://' . $_POST['fbc_flight_domain'] . '.canto.com/api/v1/';
$flight['req']      = $flight['api_url'] . $_POST['fbc_scheme'] . '/' . $_POST['fbc_id'];


//	$instance = Canto::instance();
$response = curl_action( $flight['req'], 0 );
$response = ( json_decode( $response ) );



//Get the download url
$detail = $response->url->download;
$detail = curl_action( $detail, 1 );

//OLD WAY OF GETTING FLIGHT IMAGE DOWNLOAD
//list( $httpheader ) = explode( "\r\n\r\n", $detail, 2 );
//$matches = array();
//preg_match( '/(Location:|URI:)(.*?)\?x-amz-security-token/', $httpheader, $matches );
//$location = trim( str_replace( "Location: ", "", $matches[0] ) );


	$matches = array();
	preg_match( '/(Location:|URI:)(.*?)[\n\r]/', $detail, $matches );
	$uri = str_replace( array("Location: "), "", $matches[0] );
	$location = trim( $uri );



$tmp        = download_url( $location );
$file_array = array(
	'name'     => $response->name,
	'tmp_name' => $tmp
);

// Check for download errors
if ( is_wp_error( $tmp ) ) {
	@unlink( $file_array['tmp_name'] );

	return $tmp;
}

$post_data = array(
		'post_content' => $_POST['description'],
		'post_excerpt' => $_POST['caption'],
);


if (! empty($_POST['title'])) {
	$post_data['post_title'] = $_POST['title'];
} else {
	$post_data['post_title'] = basename($file_array['name']);
}




/**
 * Check for Duplicates (existing images imported from Canto)
 */
$args = array(
  'post_type'   => 'attachment',
  'post_status' => 'inherit',
  'meta_query'  => array(
    array(
      'key'     => 'fbc_id',
      'value'   => $_POST['fbc_id']
    )
  )
);
$query = new WP_Query($args);
$posts = $query->posts;
if($posts && get_option('fbc_duplicates') === "true") {
	$id = $posts[0]->ID;

	update_post_meta ($id, '_wp_attachment_image_alt' , $_POST['alt']);
	update_post_meta ($id, 'description' , $_POST['description']);
	update_post_meta ($id, 'copyright' , $_POST['copyright']);
	update_post_meta ($id, 'terms' , $_POST['terms']);
	update_post_meta ($id, 'fbc_id' , $_POST['fbc_id']);
	update_post_meta ($id, 'fbc_scheme' , $_POST['fbc_scheme']);


	$guid = explode("/",$posts[0]->guid);
	$file = array_pop($guid);
	$meta = wp_get_attachment_metadata( $id );
	$uploads = wp_upload_dir();
	$dir_path = $uploads['path'];
	$file_sub = $uploads['subdir'];
	$file_perms = fileperms($dir_path.'/'.$file) & 0777;

	if (file_exists($dir_path.'/'.$file))
  	unlink($dir_path.'/'.$file);

	if(is_array($meta)) {
		foreach($meta["sizes"] as $size) {
			$fileName = $size["file"];
			// Create array with all old sizes for replacing in posts later
			$oldfilesAr[] = $thisfile;
			// Look for files and delete them
			if (strlen($fileName)) {
				$fp = $dir_path.'/'.$size["file"];
				if (file_exists($fp)) {
					unlink($fp);
				}
			}
		}
	}

	// Move new file to old location/name
	//$moveit = move_uploaded_file($tmp, $dir_path.'/'.$file);
	//$movefile = wp_handle_upload( $uploadedfile, array('test_form' => false) );
	$copy = copy($tmp,$dir_path.'/'.$file);

	//exit(json_encode(array($file_array['tmp_name'], $dir_path.'/'.basename($file))));
	//error_get_last()
	//exit(json_encode($copy));

	@chmod($dir_path.'/'.$file, $file_perms);

	// Make thumb and/or update metadata
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dir_path.'/'.$file ) );
	update_attached_file( $id, $dir_path.'/'.$file);
	if(file_exists($file_array['tmp_name']))
		unlink($file_array['tmp_name']);


} else {
	$id = media_handle_sideload( $file_array, $send_id, '', $post_data);
	if ( is_wp_error( $id ) ) {
		@unlink( $file_array['tmp_name'] );
		return $id;
	}
	add_post_meta ($id, '_wp_attachment_image_alt' , $_POST['alt']);
	add_post_meta ($id, 'description' , $_POST['description']);
	add_post_meta ($id, 'copyright' , $_POST['copyright']);
	add_post_meta ($id, 'terms' , $_POST['terms']);
	add_post_meta ($id, 'fbc_id' , $_POST['fbc_id']);
	add_post_meta ($id, 'fbc_scheme' , $_POST['fbc_scheme']);
}


	$attachment_url = wp_get_attachment_url( $id );

	// Additional parameters
	//$caption = $title = $align = $rel = $size = $alt = '';
	//$rel     = false;
	//$html    = get_image_send_to_editor( $id, $caption, $title, $align, $attachment_url, (bool) $rel, $size, $alt );

	$rel = $url = '';
	$html = $title = isset( $_POST['title'] ) ? $_POST['title'] : '';

		//Create the link to section here.


	$align = isset( $_POST['align'] ) ? $_POST['align'] : 'none';
	$size = isset( $_POST['size'] ) ? $_POST['size'] : 'medium';
	$alt = isset( $_POST['alt'] ) ? $_POST['alt'] : '';
	$caption = isset( $_POST['caption'] ) ? $_POST['caption'] : '';
	$title = ''; // We no longer insert title tags into <img> tags, as they are redundant.
	$html = get_image_send_to_editor( $id, $caption, $title, $align, $url, (bool) $rel, $size, $alt );


	$attachment                 = array();
	$attachment['post_title']   = $_POST['title'];
	$attachment['post_excerpt'] = $_POST['caption'];
	$attachment['image-size'] = $_POST['size'];
	$attachment['image_alt'] = $_POST['alt'];
	$attachment['align'] = $_POST['align'];
	$attachment['description'] = $_POST['description'];
	$attachment['copyright'] = $_POST['copyright'];
	$attachment['terms'] = $_POST['terms'];

	if($_POST['link'] != "none")
		$attachment['url'] = $attachment_url;
	else
		$attachment['url'] = '';

	//This filter is documented in wp-admin/includes/media.php
	$html = apply_filters( 'media_send_to_editor', $html, $id, $attachment );

	// replace wp-image-<id>, wp-att-<id> and attachment_<id>
	$html = preg_replace(
		array(
			'#(caption id="attachment_)(\d+")#', // mind the quotes!
			'#(wp-image-|wp-att-)(\d+)#'
		),
		array(
			sprintf( '${1}nsm_%s_${2}', esc_attr( $send_id ) ),
			sprintf( '${1}nsm-%s-${2}', esc_attr( $send_id ) ),
		),
		$html
	);

	$attachment_id = $id;

	$results = array("attachment" => $html, "attachment_id" => $attachment_id);


	if ( isset( $_POST['chromeless'] ) && $_POST['chromeless'] ) {
		// WP3.5+ media browser is identified by the 'chromeless' parameter
		//exit( $html );
		//header('Content-Type: application/json;charset=utf-8');
		//echo json_encode($results);
		exit(json_encode($results));
	} else {
		//return media_send_to_editor( $html );
		return media_send_to_editor(json_encode($results));
	}



//}
