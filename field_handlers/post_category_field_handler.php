<?php

function ph_migrate_post_category_handler_register()
{
	ph_migrate_register_field_handler( 'ph_post_destination','categories:','ph_migrate_post_category_handler' );
}
add_action( 'ph_migrate_register_field_handlers','ph_migrate_post_category_handler_register' );


function ph_migrate_post_category_handler($post, $fields)
{
	$metas = array();
	foreach ( $fields as $key => $value ) {
		$prop = substr( $key, strlen( 'categories:' ) );
		$metas[ $prop ] = $value;
	}
	$ids = $metas['ids'];
	if(isset($metas['taxonomy'])) {
		$taxonomies=$metas['taxonomy'];
	}
	else {
		$taxonomies='category';
	}
	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
		$taxonomies=array($taxonomies);
	}
	else {
		if( ! is_array( $taxonomies ) ) {
			$taxonomies=array($taxonomies);
		}
		while( count($ids) > count($taxonomies) ) {
			$taxonomies[]=$taxonomies[0];
		}
	}
	$copy = array();
	for($i=0;$i<count($ids);$i++)
	{
		if(!isset($copy[$taxonomies[$i]]) || !in_array($ids[$i],$copy[$taxonomies[$i]])) {
			$copy[$taxonomies[$i]][]=$ids[$i];
		}
	}
	foreach($copy as $taxonomy=>$ids) {
		wp_set_object_terms($post['ID'],$ids,$taxonomy,false);
	}
}