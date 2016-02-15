<?php

class ph_mongodb_source extends ph_source
{
	/**
	 * @var MongoDB
	 */
	public $db;
	/**
	 * @var String
	 */
	public $cm;
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
	public function __construct($db, $collection_name)
	{
		$this->db = $db;
		$this->cm = $collection_name;
		$this->collection = null;
		$fields = array();
	}

	private function getCollection(){
		if($this->collection != null) {
			$this->collection = new MongoCollection($this->db, $this->cm);
		}
		return $this->collection;
	}

	public function getIDs()
	{
		$collection = $this->getCollection();
		/**
		 * @param $cursor MongoCursor
		 */
		$cursor = $collection->find();
		$ids = array();
		while($cursor->hasNext()){
			$cursor->next();
			$ids[] = $cursor->key();
		};
		return $ids;
	}

	public function getItemByID($id)
	{
		return $this->getCollection()->findOne( array('_id' => new MongoId($id)) );
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