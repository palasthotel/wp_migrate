<?php

function ph_migrate_post_meta_handler_register()
{
	ph_migrate_register_field_handler( 'ph_post_destination','meta:','ph_migrate_post_meta_handler' );
	ph_migrate_register_field_handler( 'ph_attachment_destination','meta:','ph_migrate_post_meta_handler' );
}
add_action( 'ph_migrate_register_field_handlers','ph_migrate_post_meta_handler_register' );


function ph_migrate_post_meta_handler($post, $fields)
{
	$metas = array();
	foreach ( $fields as $key => $value ) {
		$prop = substr( $key, strlen( 'meta:' ) );
		$metas[ $prop ] = $value;
	}

	foreach ( $metas as $key => $value ) {

		// so we can handle post meta arrays
		if(!is_array($value)){
			$value = array($value);
		}

		if (is_array($post)) {
			// Sometimes we get an assoc array
			$post_id = $post['ID'];
		} else {
			// Sometimes we get an WP_Post object
			$post_id = $post->ID;
		}

		// cleanup first
		delete_post_meta( $post_id, $key );

		// than add metas
		foreach ($value as $v){
			add_post_meta( $post_id, $key, $v );
		}
	}
}