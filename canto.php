<?php
/*
 * Plugin Name: Canto
 * Version: 1.2.1
 * Plugin URI: https://www.canto.com/integrations/wordpress/
 * Description: Easily find and publish your brand and creative assets directly to wordpress without having to search through emails or folders, using digital asset management by Canto.
 * Author: Canto Inc
 * Author URI: https://www.canto.com/
 * Requires at least: 4.4
 * Tested up to: 4.9.6
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
