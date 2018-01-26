<?php

class ph_csv_source extends ph_source
{
	private $file;
	public $data;
	public $fields;

	public function __construct($file,$idcolumn=null,$fields=null,$delimiter=",",$enclosure='"',$escape='\\')
	{
		$this->file = $file;
		$fid=fopen($file,"r");
		$this->fields=$fields;
		$this->data=array();
		$line=0;
		while(($data=fgetcsv($fid,0,$delimiter,$enclosure,$escape))) {
			if($this->fields==null) {
				$this->fields=$data;
			} else {
				if($idcolumn===null) {
					$this->data[$line]=$data;
				} else {
					if(isset($this->data[$data[$idcolumn]])){

						ph_migrate_log("Line {$line} -> Id: {$data[$idcolumn]}  already exists...\n");
					}
					$this->data[$data[$idcolumn]]=$data;
				}
			}
			$line++;
		}
		fclose($fid);
	}


	/**
	 * @return mixed
	 */
	public function getFields()
	{
		return $this->fields;
	}

	function getIDs()
	{
		return array_keys($this->data);
	}

	function getItemByID($id)
	{
		$rawdata=$this->data[$id];
		$row=new stdClass();
		for($i=0;$i<count($this->fields);$i++) {
			$fieldname = $this->fields[$i];
			if(isset($rawdata[$i])){
				$row->{$fieldname}=$rawdata[$i];
			} else {
				ph_migrate_log("Item ( $id ): no data for field $fieldname found. Empty string added!\n");
				$row->{$fieldname}="";
			}

		}
		$row->ID=$id;
		return $row;
	}


	public function describeID($id)
	{

?>
		<p>Path: <?php echo esc_html( $this->file );?></p>
		<p>ID: <?php echo $id; ?><br>
			Content:
		<pre>
			<?php echo esc_html( $this->data[$id] );?>
		</pre>
		</p>
<?php
	}
}