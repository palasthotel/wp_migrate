<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-01-25
 * Time: 08:54
 */

class ph_mysql_source extends ph_source {
	/** @var \mysqli */
	protected $connection;

	private $idquery;

	private $fetchquery;

	const ID_PLACEHOLDER = '$__ID__$';

	public function __construct(mysqli $connection, $idquery, $fetchquery) {
		$this->connection = $connection;
		$this->idquery = $idquery;
		$this->fetchquery = $fetchquery;
	}

	public function getIDs() {
		$ids = [];
		$result = $this->connection->query($this->idquery);
		while ($row = $result->fetch_assoc()) {
			$ids[] = $row['ID'];
		}
		return $ids;
	}


	public function getItemByID($id) {
		if(stripos($this->fetchquery,self::ID_PLACEHOLDER) !== false) {
			$results = $this->connection->query(str_replace(self::ID_PLACEHOLDER,$id,$this->fetchquery));
		} else {
			$results = $this->connection->query($this->fetchquery . $id);
		}
		$row = $results->fetch_object();
		while($sub = $results->fetch_object()) {
			if(!isset($row->childs)) {
				$row->childs = array();
			}
			$row->childs[] = $sub;
		}
		return $row;
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
