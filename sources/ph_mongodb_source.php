<?php

class ph_mongodb_source extends ph_json_array_source
{
	/**
	 * @var MongoDB
	 */
	public $db;
	/**
	 * @var String
	 */
	public $cn;
	/**
	 * @var MongoCollection
	 */
	private $collection;
	/**
	 * @var array
	 */
	public $fields;

	/**
	 * ph_mongodb_source constructor.
	 * @param $db MongoDB
	 * @param $collection_name String
	 */
	public function __construct( MongoDB $db, $collection_name)
	{
		$this->db = $db;
		$this->cn = $collection_name;
		$this->collection = null;
		/**
		 * empty array because we use mongodb as source
		 */
		parent::__construct([]);
	}

	private function getCollection(){
		if($this->collection == null) {
			$this->collection = new MongoCollection($this->db, $this->cn);
		}
		return $this->collection;
	}

	/**
	 * @param MongoCursor $item
	 * @return mixed
	 */
	public function getItemID($item) {
		return $item->key();
	}

	public function getIDs()
	{
		/**
		 * @param $cursor MongoCursor
		 */
		$cursor = $this->getCollection()->find();
		$ids = array();
		while($cursor->hasNext()){
			$cursor->next();
			$ids[] = $cursor->key();
		};
		return $ids;
	}

	public function getItemByID($id)
	{
		$item = $this->getCollection()->findOne( array('_id' => new MongoId($id)) );
		$item["_id"] = (string) $item["_id"];
		$this->item_map[$id] = $item;
		return parent::getItemByID($id);
	}


	public function describeID($id)
	{
		$item = $this->getItemByID($id);
		?>
		<pre>
			<?php var_dump($item);  ?>
		</pre>
		<?php
	}
}