<?php

class ph_usermeta_destination extends ph_destination {
	public $fields;

	public function createItem() {
		return new StdClass();
	}

	public function getItemByID( $id ) {
		global $wpdb;
		$meta = $wpdb->get_row( "SELECT * FROM $wpdb->usermeta WHERE umeta_id = $id" );
		$item = new StdClass();
		if ( ! empty( $meta ) ) {
			$item->ID         = $id;
			$item->user_id    = $meta->user_id;
			$item->key        = $meta->meta_key;
			$item->value      = $meta->meta_value;
			$item->prev_value = $item->value;
		}

		return $item;
	}

	public function save( $item ) {
		if ( isset( $item->ID ) ) {
			return update_user_meta( $item->user_id, $item->key, $item->value, $item->prev_value );
		}

		return add_user_meta( $item->user_id, $item->key, $item->value, $item->unique );
	}

	public function deleteItem( $item ) {
		delete_user_meta( $item->user_id, $item->key, $item->value );
	}
}
