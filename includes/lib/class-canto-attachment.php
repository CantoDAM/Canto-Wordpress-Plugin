<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Copyright and Terms & Condition fields to media uploader
 *
 * @param $form_fields array, fields to include in attachment form
 * @param $post object, attachment record in database
 * @return $form_fields, modified form fields
 */

function be_attachment_field_credit( $form_fields, $post ) {
	$form_fields['copyright'] = array(
			'label' => 'Copyright',
			'input' => 'text',
			'value' => get_post_meta( $post->ID, 'copyright', true )
	);

	$form_fields['terms'] = array(
			'label' => '<span style="padding-top: 0px; margin-top: -8px">Terms &<br/> Conditions</span>',
			'input' => 'text',
			'value' => get_post_meta( $post->ID, 'terms', true )
	);

	return $form_fields;
}

add_filter( 'attachment_fields_to_edit', 'be_attachment_field_credit', 10, 2 );

/**
 * Save values of Copyright and Terms & Condition in media uploader
 *
 * @param $post array, the post data for database
 * @param $attachment array, attachment fields from $_POST form
 * @return $post array, modified post data
*/

function be_attachment_field_credit_save( $post, $attachment ) {
	if( isset( $attachment['copyright'] ) )
		update_post_meta( $post['ID'], 'copyright', $attachment['copyright'] );

	if( isset( $attachment['terms'] ) )
		update_post_meta( $post['ID'], 'terms', esc_url( $attachment['terms'] ) );

	return $post;
}

add_filter( 'attachment_fields_to_save', 'be_attachment_field_credit_save', 10, 2 );
