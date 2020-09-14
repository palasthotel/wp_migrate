<?php

class ph_json_array_source extends ph_source
{
	/**
	 * @var array
	 */
	public $id_field_path;
	public $json;
	public $item_map;
	public $fields;

	public function __construct($json_array)
	{
		$this->json = $json_array;
		$this->id_field_path = array('id');
		$this->fields = array();
	}



	/**
	 * @param $item array
	 * @return mixed
	 */
	public function getItemID($item){
		return $this->getPathValue($item, $this->id_field_path);
	}

	/**
	 * @param $item array
	 * @param $key_path array
	 * @return mixed
	 */
	public function getPathValue($item, $key_path){
		$tmp = $item;
		foreach($key_path as $part){
			if(empty($tmp[$part])) return "";
			$tmp = $tmp[$part];
		}
		return $tmp;
	}

	/**
	 * @param $id_field_path array
	 */
	public function setIDFieldPath($id_field_path){
		$this->id_field_path = $id_field_path;
	}

	/**
	 * get ids
	 * @return array
	 */
	public function getIDs()
	{
		$ids = array();
		foreach ( $this->json as $item ) {
			$id = $this->getItemID($item);
			$ids[] = $id;
			$this->item_map[$id] = $item;
		}
		return $ids;
	}

	/**
	 * build a row item
	 * @param $id
	 * @return \Stdclass
	 */
	public function getItemByID($id)
	{
		$item = $this->item_map[$id];
		$row = new Stdclass();
		foreach ( $this->fields as $fieldname => $key_path ) {
			$value = $this->getPathValue($item,$key_path);
			$row->{$fieldname} = $value;
		}
		return $row;
	}

	/**
	 * debug a item
	 * @param $id
	 */
	public function describeID($id)
	{
		$this->getIDs();
		$item = $this->getItemByID($id);
		?>
		<p>ID Field:Item ID: <?php echo $id; ?></p>
		<pre>
			<?php
			foreach($item as $field => $value){
				echo "<p><strong>$field</strong>: $value</p>";
			}
			?>
		</pre>
		<?php
	}

	/**
	 * set key path for a field
	 * @param $fieldname string
	 * @param $key_path array
	 */
	public function addField($fieldname, $key_path)
	{
		$this->fields[ $fieldname ] = $key_path;
	}
}