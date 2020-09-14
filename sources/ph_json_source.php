<?php

class ph_json_source extends ph_source
{
	public $path;

	public $fields;

	public function __construct($path)
	{
		$this->path = $path;
		$fields = array();
	}

	public function getIDs()
	{
		$files = glob( $this->path.'/*.json' );
		$ids = array();
		foreach ( $files as $file ) {
			$data = explode( '/',$file );
			$data = $data[ count( $data ) - 1 ];
			$data = explode( '.',$data );
			$ids[] = intval( $data[0] );
		}
		return $ids;
	}

	public function getItemByID($id)
	{
		return json_decode(file_get_contents( $this->path.'/'.$id.'.json' ));
	}

	public function addField($fieldname, $xpath)
	{
		$this->fields[ $fieldname ] = $xpath;
	}

	public function describeID($id)
	{
		$file = $this->path.'/'.$id.'.json' ;
		if(!is_file($file)) {
			echo "<p>Keine Quelldatei gefunden. $file</p>";
			return;
		}
		$content = json_decode(file_get_contents( $file ));
?>
		<p>Path: <?php echo esc_html( $this->path );?>/<?php echo esc_html( $id );?>.json</p>
		<p>Content:
		<pre>
        <?php echo esc_html( json_encode($content, JSON_PRETTY_PRINT), ENT_COMPAT,'UTF-8' );?>
		</pre></p>
<?php
	}
}
