<?php
	
class ph_placeholder_source extends ph_source
{
	public $fields;
	
	public function hasID($id)
	{
		return true;
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