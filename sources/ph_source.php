<?php

class ph_source
{
	public $fields;

	public function getIDs()
	{
		return array();
	}
	
	public function hasID($id)
	{
		static $ids=null;
		if($ids==null) {
            $ids = $this->getIDs();
        }
		return in_array($id, $ids);
	}

	public function getItemByID($id)
	{
		return null;
	}

	public function describeID($id)
	{
		return;
	}
}