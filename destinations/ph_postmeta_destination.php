<?php
class ph_postmeta_destination extends ph_destination
{
	public $fields;

	public function createItem()
	{
		return new StdClass();
	}

	public function getItemByID($id)
	{
    global $wpdb;
    $meta = $wpdb->get_row( "SELECT * FROM $wpdb->postmeta WHERE meta_id = $id" );
    $item = new StdClass();
    if( !empty($meta) ) {
      $item->ID = $id;
      $item->post_id = $meta->post_id;
      $item->key = $meta->meta_key;
      $item->value = $meta->meta_value;
      $item->prev_value = $item->value; 
    }
		return $item;
	}

	public function save($item)
	{
  	if( isset($item->ID) ) {
    	return update_post_meta($item->post_id, $item->key, $item->value, $item->prev_value);
  	}
    return add_post_meta($item->post_id, $item->key, $item->value, $item->unique);
	}

	public function deleteItem($item)
	{
    delete_post_meta( $item->post_id, $item->key, $item->value );
	}
}