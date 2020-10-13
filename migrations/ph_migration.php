<?php

class ph_migration
{
	public $name;
	public $destination;
	public $source;
	public $mappings;

	public function addSimpleMapping($field){
		return $this->addFieldMapping($field, $field);
	}

	public function addFieldMapping($sourcefield, $destfield)
	{
		if ( $this->mappings == null ) {
			$this->mappings = array();
		}
		$elem = new ph_field_mapping( $sourcefield,$destfield );
		$this->mappings[] = $elem;
		return $elem;
	}

	public function removeFieldMapping($destfield)
	{
		unset($this->mappings[ $destfield ]);
	}

	public function map($source, $destination)
	{
		foreach ( $this->mappings as $field ) {
			$field->map( $source,$destination );
		}
	}

	public function prepareRow($row, $source_id)
	{
		return $row;
	}

	public function createStub($id)
	{
		return null;
	}

	/**
	 * @param $source_id
	 * @param bool $createIfNeeded
	 *
	 * @return int|null
	 */
	public function getDestinationIDForSourceID($source_id, $createIfNeeded = true)
	{
        $this->prepareTable();
        global $wpdb;
	    static $mapping = null;
	    if($mapping == null) {
            $input = $wpdb->get_results( 'select source_id,dest_id,needs_import from '.$wpdb->prefix.'ph_migrate_map_'.$this->name );
            $mapping=array();
            foreach($input as $map_entry) {
                $mapping[$map_entry->source_id]=$map_entry;
            }

        }
//		$data = $wpdb->get_results( 'select source_id,dest_id,needs_import from '.$wpdb->prefix.'ph_migrate_map_'.$this->name." where source_id='".$source_id."'" );
		if ( !isset($mapping[$source_id]) || $mapping[$source_id]->dest_id == null ) {
			if ( ! $createIfNeeded ) { return null; }
			$source = $this->createStub( $source_id );
			if ( $source == null ) { return null; }
			$id = $this->destination->save( $source );
			if ( !isset($mapping[$source_id]) ) {
				$wpdb->insert( $wpdb->prefix.'ph_migrate_map_'.$this->name,array( 'source_id' => $source_id, 'dest_id' => $id, 'needs_import' => true ) ); }
			else
			{
				$wpdb->update( $wpdb->prefix.'ph_migrate_map_'.$this->name,array( 'source_id' => $source_id, 'dest_id' => $id, 'needs_import' => true ), array( 'source_id' => $source_id ) );
			}
			$mapping = null;
			return intval($id);
		}
		else
		{
			return intval($mapping[$source_id]->dest_id);
		}
	}

	public function getSourceIDForDestinationID($destination_id)
	{
		$this->prepareTable();
		global $wpdb;
		$data = $wpdb->get_results( 'select source_id from '.$wpdb->prefix.'ph_migrate_map_'.$this->name.' where dest_id='.$destination_id );
		if ( 0 == count( $data ) || $data[0]->source_id == null ) {
			return null; }
		else
		{
			return $data[0]->source_id;
		}
	}

	public function prepareTable()
	{
		global $wpdb;
		$table_name = $wpdb->prefix.'ph_migrate_map_'.$this->name;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
		  source_id varchar(190) NOT NULL,
		  dest_id bigint,
		  needs_import bigint,
		  PRIMARY KEY  (source_id)
		) $charset_collate;";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
	}

	public function getNumberOfImportedItems()
	{
		$this->prepareTable();
		global $wpdb;
		$table_name = $wpdb->prefix.'ph_migrate_map_'.$this->name;
		$result = $wpdb->get_results( "select count(*) as num from $table_name where dest_id is not null and (needs_import=0 or needs_import is null)",ARRAY_A );
		return $result[0]['num'];
	}

	public function getNumberOfAvailableItems()
	{
		$this->prepareTable();
		return count( $this->source->getIDs() );
	}

	public function getDelta()
	{
		$this->prepareTable();
		return $this->getNumberOfAvailableItems() -$this->getNumberOfImportedItems();
	}
}
