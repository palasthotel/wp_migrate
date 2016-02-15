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
		return $item->ID;
	}

	public function deleteItem($item)
	{
		wp_delete_user( $item->ID );
	}

}