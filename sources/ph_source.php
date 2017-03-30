<?php

class ph_source
{
	public $fields;
	private $ids=null;

	public function getIDs()
	{
		return array();
	}
	
	public function hasID($id)
	{
		if($this->ids==null) {
            $this->ids = $this->getIDs();
        }
        return in_array($id,$this->ids);
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