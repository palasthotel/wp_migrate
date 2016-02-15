<?php

class ph_json_array_source extends ph_source
{
	public $file;
	public $id_field_path;
	public $json;
	public $item_map;
	public $fields;

	public function __construct($json_array)
	{
		$this->json = $json_array;
		$this->id_field_path = array('id');
		$fields = array();
	}

	public function getItemID($item){
		$tmp = $item;
		foreach($this->id_field_path as $part){
			$tmp = $tmp[$part];
		}
		return $tmp;
	}

	public function setIDFieldPath($id_field_path){
		$this->id_field_path = $id_field_path;
	}

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

	public function getItemByID($id)
	{
		return $this->item_map[$id];
	}


	public function describeID($id)
	{
		$item = $this->item_map[$id];
		?>
		<p>ID Field: <?php echo $this->id_field_path; ?> Item ID: <?php echo $id; ?></p>
		<pre>
			<?php var_dump($item);  ?>
		</pre>
		<?php
	}
}