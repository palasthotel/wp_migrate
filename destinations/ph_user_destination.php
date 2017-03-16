<?php

require_once(ABSPATH.'wp-admin/includes/user.php');

class ph_user_destination extends ph_destination
{
	
	public $user_fields;
	
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
	
	/**
	 * get all field handlers
	 * @return array
	 */
	public function get_field_handlers(){
		return ph_migrate_get_field_handlers($this);
	}
	
	/**
	 * get process list and save unhandled properties to $user global
	 * @param $item
	 * @param $field_handlers
	 *
	 * @return array
	 */
	public function get_processes($item, $field_handlers, &$rest){
		return ph_migrate_get_process($item, $field_handlers, $rest);
	}
	
	/**
	 * save user fields to user
	 * create user if needed
	 * @param $item object
	 *
	 */
	public function save_user_data(&$item){
		if ( ! isset($item->ID) ) {
			$userdata = array();
			foreach($this->user_fields as $valid){
				if(!empty($item->{$valid})){
					$userdata[$valid] = $item->{$valid};
				}
			}
			$id = wp_insert_user( $userdata );
			if(is_wp_error($id)){
				echo $id->get_error_message();
				return;
			}
			$item->ID = $id;
			ph_migrate_statistics_increment("Users created",1);
		}
		else
		{
			ph_migrate_statistics_increment("Users updated",1);
		}
		wp_update_user( $item );
	}
	
	/**
	 * save data from field handlers
	 * @param $item
	 * @param $user_process
	 */
	public function save_user_field_handlers($item, $user_process){
		$user = get_userdata($item->ID);
		foreach ( $user_process as $key => $dataset ) {
			$callback = $dataset['callback'];
			$callback($user,$dataset['fields']);
		}
	}
	
	/**
	 * migration calls this to save migration item
	 * @param $item
	 *
	 * @return mixed
	 */
	public function save($item)
	{
		$core_user = (object)array();
		/**
		 * separate all field handler properties from core properties
		 */
		$process = $this->get_processes($item, $this->get_field_handlers(), $core_user);
		
		/**
		 * save core user data
		 */
		$this->save_user_data($core_user);
		$item->ID = $core_user->ID;
		
		/**
		 * save data from field handlers
		 */
		$this->save_user_field_handlers($item, $process);

		return $item->ID;
	}

	public function deleteItem($item)
	{
		wp_delete_user( $item->ID );
	}

}