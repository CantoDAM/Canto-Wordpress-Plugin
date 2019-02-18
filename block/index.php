<?php

/**
 * Plugin Name: Gutenberg Examples Basic
 * Plugin URI: https://github.com/WordPress/gutenberg-examples
 * Description: This is a plugin demonstrating how to register new blocks for the Gutenberg editor.
 * Version: 1.0.2
 * Author: the Gutenberg Team
 *
 * @package gutenberg-examples
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load all translations for our plugin from the MO file.
 */
add_action( 'init', 'canto_textdomain' );

function canto_textdomain() {
	load_plugin_textdomain( 'canto', false, basename( __DIR__ ) . '/languages' );
}

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * Passes translations to JavaScript.
 */
function canto_register_block() {

	if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
	}

	$translation_array = array(
		'FBC_URL' 	=> FBC_URL,
		'FBC_PATH' 	=> FBC_PATH,
		'FBC_SITE'	=> get_bloginfo('url')
	);

	wp_register_script(
		'canto',
		plugins_url( 'block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-data', 'underscore' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'block.js' )
	);

	wp_localize_script( 'canto', 'args', $translation_array );


	register_block_type( 'canto/canto-block', array(
		'editor_script' => 'canto',
	) );

  if ( function_exists( 'wp_set_script_translations' ) ) {
    /**
     * May be extended to wp_set_script_translations( 'my-handle', 'my-domain',
     * plugin_dir_path( MY_PLUGIN ) . 'languages' ) ). For details see
     * https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
     */
    wp_set_script_translations( 'canto', 'canto' );
  }

}
add_action( 'init', 'canto_register_block' );


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
