<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// @see https://wordpress.stackexchange.com/questions/24736/wordpress-sanitize-array
function p2papi_sanitize_text_or_array_field( $array_or_string ) {
	if( is_string( $array_or_string ) ){
		$array_or_string = sanitize_text_field( $array_or_string );
	} elseif ( is_array( $array_or_string ) ){
		foreach ( $array_or_string as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = p2papi_sanitize_text_or_array_field( $value );
			}
			else {
				$value = sanitize_text_field( $value );
			}
		}
	}
	return $array_or_string;
}

?>