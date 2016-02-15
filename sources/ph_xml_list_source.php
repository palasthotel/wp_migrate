<?php

class ph_xml_list_source extends ph_source
{
	public $filename;
	
	public $items_xpath;
	
	public $id_xpath;

	public $fields;
	
	public $content;

	public $idmap;
	
	public function __construct($filename,$items_xpath, $id_xpath)
	{
		$this->filename = $filename;
		$this->items_xpath = $items_xpath;
		$this->id_xpath = $id_xpath;
		$fields = array();
	}
	
	private function loadIfNeeded()
	{
		if($this->content==NULL)
		{
			if(is_file($this->filename)){
				$content = file_get_contents( $this->filename );
				$content = preg_replace( '/xmlns(:[^=]*)?="[^"]*"/um', '', $content );
				$content = str_replace("&#x19;", "&apos;", $content);
				$xml = simplexml_load_string( $content );
				$this->content=$xml;		
			}else{
				$this->content=simplexml_load_string("<?xml version='1.0' encoding='UTF-8'?><xml></xml>");
			}	
		}
	}

	public function getIDs()
	{
		$this->loadIfNeeded();
		if(is_object($this->content) and get_class($this->content)=="SimpleXMLElement"){
			$items=$this->content->xpath($this->items_xpath);
		}
		$ids=array();
		foreach($items as $item)
		{
			$id=$item->xpath($this->id_xpath);
			$id=$id[0];
			$id=(string)$id;
			$ids[]=$id;
		}
		return $ids;
	}

	public function getItemByID($id)
	{
		$this->loadIfNeeded();
		$row = new Stdclass();
		$row->ID = $id;
		if($this->idmap==NULL)
		{
			$items = $this->content->xpath($this->items_xpath);
			$this->idmap=array();
			foreach($items as $item)
			{
				$_id=$item->xpath($this->id_xpath);
				$_id=$_id[0];
				$_id=(string)$_id;
				$this->idmap[$_id]=$item;
			}			
		}
		$row->xml=$this->idmap[$id];
		foreach ( $this->fields as $key => $query ) {
			$data = $row->xml->xpath( $query );
			$values = array();
			foreach ( $data as $entry ) {
				$result = $entry->asXML();
				$tag = $entry->getName();
				$result = preg_replace( '+(^<'.$tag.'[^>]*>(.*)</'.$tag.'>$|^.*'.$tag.'="([^"]*)"$)+us', '$2$3', $result );
				$values[] = $result;
			}
			if ( 0 == count( $values ) ) { continue; }
			if ( 1 == count( $values ) ) {
				$values = $values[0]; }
			$row->{$key} = $values;
		}
		return $row;
	}

	public function addField($fieldname, $xpath)
	{
		$this->fields[ $fieldname ] = $xpath;
	}
}