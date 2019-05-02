<?php

class ph_xml_source extends ph_source
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
		$files = glob( $this->path.'/*.xml' );
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
		$content = file_get_contents( $this->path.'/'.$id.'.xml' );
		$content = preg_replace( '/xmlns(:[^=]*)?="[^"]*"/um', '', $content );
		$xml = simplexml_load_string( $content,"SimpleXMLElement",LIBXML_NOCDATA );
		$row = new Stdclass();
		$row->ID = $id;
		$row->xml = $xml;
		foreach ( $this->fields as $key => $query ) {
			$data = $xml->xpath( $query );
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

	public function describeID($id)
	{
		$file = $this->path.'/'.$id.'.xml' ;
		if(!is_file($file)) {
			echo "<p>Keine Quelldatei gefunden. $file</p>";
			return;
		}
		$content = file_get_contents( $file );
		$content = preg_replace( '/xmlns(:[^=]*)?="[^"]*"/um', '', $content );
		$xml = simplexml_load_string( $content );
		$dom=new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->saveXML());
		$output=$dom->saveXML();
?>
		<p>Path: <?php echo esc_html( $this->path );?>/<?php echo esc_html( $id );?>.xml</p>
		<p>Content:
		<pre>
<?php echo esc_html( $output,ENT_COMPAT,'UTF-8' );?>
		</pre></p>
<?php
	}
}
