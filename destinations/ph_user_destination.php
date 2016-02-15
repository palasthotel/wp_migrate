<?php

require_once(ABSPATH.'wp-admin/includes/user.php');

class ph_user_destination extends ph_destination
{
	public function createItem()
	{
		return new StdClass();
	}

	public function getItemByID($id)
	{
		$post = new WP_User( $id );
		return $post;
	}

	public function save($item)
	{
		if ( ! isset($item->ID) ) {
			$userdata = array(
  		  'user_login' => $item->username,
  		  'user_pass' => $item->password,
  		  'user_email' => $item->email,
  		  'display_name' => $item->username,
  		  'nickname' => $item->username,
  		  'role'  => 'author',
			);
			$id = wp_insert_user( $userdata );
			$item->ID = $id;
			ph_migrate_statistics_increment("Users created",1);
		}
		else
		{
			ph_migrate_statistics_increment("Users updated",1);
		}
		wp_update_user( $item );
		return $id;
	}

	public function deleteItem($item)
	{
		wp_delete_user( $item->ID );
	}

}