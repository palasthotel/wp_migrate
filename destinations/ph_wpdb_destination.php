<?php

/**
 * @property string tablename
 * @property string id_field
 */
class ph_wpdb_destination extends ph_destination {

	/**
	 * ph_sql_destination constructor.
	 *
	 * @param string $tablename
	 * @param string $id_field
	 */
	public function __construct( string $tablename, $id_field ) {
		$this->tablename          = $tablename;
		$this->id_field           = $id_field;
	}

	/**
	 * @return \wpdb
	 */
	public function wpdb(){
		global $wpdb;
		return $wpdb;
	}

	/**
	 * @return \stdClass
	 */
	public function createItem() {
		return new stdClass();
	}

	/**
	 * @param $id
	 *
	 * @return object|\stdClass|null
	 */
	public function getItemByID( $id ) {
		$table = $this->tablename;
		$id_field = $this->id_field;
		return $this->wpdb()->get_row("SELECT * FROM $table WHERE $id_field = $id ORDER BY $id_field ASC");
	}

	/**
	 * @param $item
	 *
	 * @return int
	 */
	public function save( $item ) {
		$item = (array) $item;
		if(isset($item[$this->id_field])){
			// update
			$this->wpdb()->replace($this->tablename, $item );
			return $item[$this->id_field];
		}
		// insert
		$this->wpdb()->insert($this->tablename,	$item );
		return ($this->wpdb()->insert_id > 0)? $this->wpdb()->insert_id: null;
	}

	/**
	 * @param $item
	 */
	public function deleteItem( $item ) {
		$this->wpdb()->delete($this->tablename, array(
			$this->id_field => $item->{$this->id_field}
		));
	}


}