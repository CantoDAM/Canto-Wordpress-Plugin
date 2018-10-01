<?php
define('WP_ADMIN', FALSE);
define('WP_LOAD_IMPORTERS', FALSE);

if ( ! function_exists( 'et_core_portability_register' ) ) {
	function et_core_portability_register( $context, $args ) {
		return true;
	}
}

require_once(urldecode($_GET["abspath"]).'wp-admin/admin.php');
if(!function_exists('wp_handle_upload')) {
  require_once( $_POST["abspath"] . 'wp-admin/includes/image.php' );
}

$sizes = apply_filters( 'image_size_names_choose', array(
    'thumbnail' => __( 'Thumbnail' ),
    'medium'    => __( 'Medium' ),
    'large'     => __( 'Large' ),
    'full'      => __( 'Full Size' ),
) );

$thesizes = get_image_sizes();

if(!in_array('full',$thesizes))
  $thesizes['full'] = array();

foreach($thesizes as $k => $v){
  $dimensions = (isset($v['width'])) ? ' - '.$v['width'].' x '.$v['height'] : '';
  $thesizes[$k]['name'] = $sizes[$k].$dimensions;
}
//$thesizes = apply_filters( 'intermediate_image_sizes_advanced', $thesizes, $metadata );

$html = '<select data-user-setting="imgsize" data-setting="size" name="size" className="size">';

foreach ($thesizes as $value => $name) {
  if($value != "medium_large") {
    $html .= '<option value="'.esc_attr( $value ).'">';
    $html .= esc_html( $name['name'] ).'</option>';
  }
}

$html .= '</select>';
echo $html;

?>
