<?php

class ph_post_destination extends ph_destination
{
	public function createItem()
	{
		return new StdClass();
	}

	public function getItemByID($id)
	{
		$post = get_post( $id );
		setup_postdata($post);
		return $post;
	}

	public function save($item)
  {
    	global $post;
		$post = array();
		$metas = array();
		global $ph_migrate_field_handlers;
		$field_handlers = array();
		$class=get_class( $this );
		while( FALSE != $class )
		{
			if( isset( $ph_migrate_field_handlers[ $class ] ) )
			{
				$field_handlers=array_merge( $field_handlers, $ph_migrate_field_handlers[ $class ] );
			}
			$class = get_parent_class( $class );
		}
		$postprocess = array();
		foreach ( $item as $property => $value ) {
			$handled = false;
			foreach ( $field_handlers as $key => $callback ) {
				if ( 0 === strpos( $property,$key ) ) {
					$handled = true;
					if ( ! isset($postprocess[ $key ]) ) {
						$postprocess[ $key ] = array( 'callback' => $callback, 'fields' => array() );
					}
					$postprocess[ $key ]['fields'][ $property ] = $value;
				}
			}
			if ( ! $handled ) {
				$post[ $property ] = $value;
			}
		}
		if ( isset($post['ID']) ) {
			wp_update_post( $post );
			if ( isset($post['post_format']) ) {
				set_post_format( $post['ID'],$post['post_format'] );
			}
			$id = $post['ID'];
			ph_migrate_statistics_increment("Posts (Type:".$post['post_type'].") updated",1);
		}
		else
		{
			$id = wp_insert_post( $post);
			$post['ID'] = $id;
			if ( isset($post['post_format']) ) {
				set_post_format( $post['ID'],$post['post_format'] );
			}
			ph_migrate_statistics_increment("Posts (Type:".$post['post_type'].") created",1);
		}
		foreach ( $postprocess as $key => $dataset ) {
			$callback = $dataset['callback'];
			$callback($post,$dataset['fields']);
		}

		return $id;
	}

	public function deleteItem($item)
	{
		$args = array(
			'post_parent' => $item->ID,
		);
		$children = get_children( $args );
		foreach ( $children as $child ) {
			wp_delete_post( $child->ID,true );
		}
		wp_delete_post( $item->ID,true );
	}

}