<?php

function ph_migrate_category_meta_handler_register()
{
	ph_migrate_register_field_handler( 'ph_category_destination','meta:','ph_migrate_category_meta_handler' );
	ph_migrate_register_field_handler( 'ph_attachment_destination','meta:','ph_migrate_category_meta_handler' );
}
add_action( 'ph_migrate_register_field_handlers','ph_migrate_category_meta_handler_register' );


function ph_migrate_category_meta_handler($term, $fields)
{
	$metas = array();
	foreach ( $fields as $key => $value ) {
		$prop = substr( $key, strlen( 'meta:' ) );
		$metas[ $prop ] = $value;
	}
	foreach ( $metas as $key => $value ) {
  	//The first thing this function will do is make sure that $meta_key already exists on $term_id. If it does not, add_term_meta($term_id, $meta_key, $meta_value) is called instead and its result is returned.
		update_term_meta( $term->term_id, $key, $value );
	}
}