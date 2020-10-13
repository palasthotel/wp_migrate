<?php

function ph_migrate_user_meta_handler_register()
{
	ph_migrate_register_field_handler( 'ph_user_destination','meta:','ph_migrate_user_meta_handler' );
}
add_action( 'ph_migrate_register_field_handlers','ph_migrate_user_meta_handler_register' );

/**
 * @param $user WP_User
 * @param $fields array
 */
function ph_migrate_user_meta_handler($user, $fields)
{
	$metas = array();
	foreach ( $fields as $key => $value ) {
		$prop = substr( $key, strlen( 'meta:' ) );
		$metas[ $prop ] = $value;
	}
	foreach ( $metas as $key => $value ) {
		delete_user_meta($user->ID, $key);
		add_user_meta( $user->ID, $key, $value, true);
	}
}
