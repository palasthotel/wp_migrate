<?php

require_once(ABSPATH.'wp-admin/includes/user.php');

class ph_user_destination extends ph_destination
{
	public function __construct() {
		$this->user_fields = array(
			'ID',
			'user_pass',
			'user_login',
			'WordPress',
			'user_nicename',
			'user_url',
			'user_email',
			'display_name',
			'for',
			'nickname',
			'first_name',
			'last_name',
			'description',
			'rich_editing',
			'user_registered',
			'role',
			'jabber',
			'aim',
			'yim',
			'show_admin_bar_front',
		);
	}

	public function createItem()
	{
		return new StdClass();
	}

	/**
	 * @param $id
	 * @return \WP_User
	 */
	public function getItemByID($id)
	{
		$user = new WP_User( $id );
		return $user;
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

		$userprocess = array();
		foreach ( $item as $property => $value ) {
			$handled = false;
			foreach ( $field_handlers as $key => $callback ) {
				if ( 0 === strpos( $property,$key ) ) {
					$handled = true;
					if ( ! isset($userprocess[ $key ]) ) {
						$userprocess[ $key ] = array( 'callback' => $callback, 'fields' => array() );
					}
					$userprocess[ $key ]['fields'][ $property ] = $value;
				}
			}
			if ( ! $handled ) {
				$post[ $property ] = $value;
			}
		}

		if ( ! isset($item->ID) ) {
			$userdata = array();
			foreach($this->user_fields as $valid){
				if(!empty($item->{$valid})){
					$userdata[$valid] = $item->{$valid};
				}
			}
			$id = wp_insert_user( $userdata );
			$item->ID = $id;
			ph_migrate_statistics_increment("Users created",1);
		}
		else
		{
			ph_migrate_statistics_increment("Users updated",1);
		}
		wp_update_user( $item );

		$user = get_userdata($item->ID);
		foreach ( $userprocess as $key => $dataset ) {
			$callback = $dataset['callback'];
			$callback($user,$dataset['fields']);
		}

		return $item->ID;
	}

	public function deleteItem($item)
	{
		wp_delete_user( $item->ID );
	}

}