<?php
class ph_categorymeta_destination extends ph_destination
{
	public $fields;

	public function createItem()
	{
		return new StdClass();
	}

	public function getItemByID($id)
	{
		global $wpdb;
		$meta = $wpdb->get_row( "SELECT * FROM $wpdb->termmeta WHERE meta_id = $id" );
		$item = new StdClass();
		if( !empty($meta) ) {
  		$item->ID = $id;
  		$item->term_id = $meta->term_id;
  		$item->key = $meta->meta_key;
  		$item->value = maybe_unserialize($meta->meta_value);
  		$item->prev_value = $item->value;
    }
		return $item;
	}

	public function save($item)
	{
		global $wpdb;
		if( isset($item->ID) ) {
			$wpdb->update($wpdb->termmeta,array('term_id'=>$item->term_id,'meta_key'=>$item->key,'meta_value'=>maybe_serialize($item->value)),array('meta_id'=>$item->ID));
			return $item->ID;
		} else {
			$wpdb->insert($wpdb->termmeta,array('term_id'=>$item->term_id,'meta_key'=>$item->key,'meta_value'=>maybe_serialize($item->value)));
			return $wpdb->insert_id;
		}
	}

	public function deleteItem($item)
	{
		global $wpdb;
		$wpdb->delete($wpdb->termmeta,array('meta_id'=>$item->ID));
	}
}