<?php
/*
 * Plugin Name: Canto
 * Version: 1.3.0
 * Plugin URI: https://www.canto.com/integrations/wordpress/
 * Description: Easily find and publish your brand and creative assets directly to wordpress without having to search through emails or folders, using digital asset management by Canto.
 * Author: Canto Inc
 * Author URI: https://www.canto.com/
 * Requires at least: 5.0
 * Tested up to: 5.0.3
 *
 * Text Domain: canto
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Canto
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FBC_PATH', plugin_dir_path(__FILE__) );
define( 'FBC_URL', plugin_dir_url(__FILE__) );
define( 'FBC_DIR', plugin_basename( __FILE__ ) );

// Load plugin class files
require_once( 'includes/class-canto.php' );
require_once( 'includes/class-canto-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-canto-admin-api.php' );
require_once( 'includes/lib/class-canto-media.php' );
require_once( 'includes/lib/class-canto-attachment.php' );

//Gutenberg Block
require_once( 'block/index.php' );

/**
 * Returns the main instance of Canto to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Canto
 */
function Canto () {
	$instance = Canto::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Canto_Settings::instance( $instance );
	}

	return $instance;
}

Canto();



/*
function canto_enqueue_block_editor_assets() {
	// Scripts.
	wp_enqueue_script(
		'canto-block',
		FBC_URL . 'block/block.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element' )
	);

	// Styles.
	wp_enqueue_style(
		'canto-block-editor',
		FBC_URL . 'assets/css/editor.css',
		array( 'wp-edit-blocks' )
	);
}
add_action( 'init', 'canto_enqueue_block_editor_assets' );

function canto_enqueue_block_assets() {
	wp_enqueue_style(
		'canto-frontend',
		FBC_URL . 'assets/css/style.css',
		array( 'wp-blocks' )
	);
}
add_action( 'init', 'canto_enqueue_block_assets' );
*/
