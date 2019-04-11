<?php

class ph_term_destination extends ph_destination
{
	public function createItem()
	{
        return new StdClass();
	}

	public function getItemByID($id)
	{
        $term=WP_Term::get_instance($id);
        $term->ID=$id;
        return WP_Term::get_instance($id);
	}

	public function save($item)
    {
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
        $id=NULL;
        if(isset($item->term_id)) {
            wp_update_term($item->term_id,$item->taxonomy,(array)$item);
            $id=$item->term_id;
        } else {
            $result=wp_insert_term($item->name,$item->taxonomy,(array)$item);
            if(is_wp_error($result)){
            	return NULL;
            }
            $id=$result['term_id'];
            $item->ID=$id;
            $item->term_id=$id;
        }
        foreach ( $postprocess as $key => $dataset ) {
            $callback = $dataset['callback'];
            $callback($item,$dataset['fields']);
        }
        return $id;
	}

    public function deleteItem($item)
    {
        wp_delete_term($item->term_id,$item->taxonomy);
    }


}