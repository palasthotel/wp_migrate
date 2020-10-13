<?php

class ph_field_mapping
{
	public $sourcefield;
	public $destinationfield;
	public $sourcemigration;
	public $staticValue;
	public $mappings;
	public $mapperClass;
	public $jointoken;

	static $migrations = null;

	public function __construct($source, $dest)
	{
		$this->sourcefield = $source;
		$this->destinationfield = $dest;
	}

	public function sourceMigration($migration)
	{
		if(!is_array($migration))
		{
			$migration=array($migration);
		}
		$this->sourcemigration = $migration;
		return $this;
	}

	public function staticValue($value)
	{
		$this->staticValue = $value;
		return $this;
	}

	public function mapValues($mappings)
	{
		$this->mappings = $mappings;
		return $this;
	}

	public function mapClass($class)
	{
		$this->mapperClass = $class;
		return $this;
	}

	public function join($jointoken)
	{
		$this->jointoken = $jointoken;
	}

	public function map($source, $destination)
	{
		$value = null;
		if ( $this->staticValue !== null ) {
			$value = $this->staticValue;
		}
		else if ( isset($source->{$this->sourcefield}) ) {
			$value = $source->{$this->sourcefield};
		}
		if ( $this->mappings != null ) {
			if ( is_array( $value ) ) {
				$new = array();
				foreach ( $value as $val ) {
					if( !is_array($val) && isset( $this->mappings[ $val ] ) ) {
						$new[] = $this->mappings[ $val ];
					}
					else
					{
						$new[] = $val;
					}
				}
				$value = $new;
			}
			else
			{
				$value = $this->mappings[ $value ];
			}
		}
		if ( $this->mapperClass != null ) {
			if ( is_array( $value ) ) {
				$new = array();
				foreach ( $value as $val ) {
					$result = $this->mapperClass->map( $val );
					if ( $result != null ) {
						$new[] = $result; 
					}
				}
				$value = $new;
			}
			else
			{
				$value = $this->mapperClass->map( $value );
			}
		}
		if ( $this->sourcemigration != null ) {
		    if(self::$migrations == null) {
		        self::$migrations=ph_migrate_migrations();
            }
			if ( is_array( $value ) ) {
				$new_values = array();
				foreach ( $value as $src ) {
					$found=false;
					foreach($this->sourcemigration as $source)
					{
					    /** @var ph_migration $sourcemigration */
						$sourcemigration=self::$migrations[$source];
						if($sourcemigration->source==null)
						{
							echo "$source Migration has no source set and is used as a sourceMigration! That's not allowed.\n";
						}
						if($sourcemigration->source->hasID($src))
						{
							$new_values[]=intval($sourcemigration->getDestinationIDForSourceID($src));
							$found=true;
							break;
						}
					}
					if(!$found)
					{
						$new_values[]=null;
					}
				}
				$value = $new_values;
			}
			else
			{
				$found=false;
				foreach($this->sourcemigration as $source)
				{
				    /** @var ph_migration $sourcemigration */
					$sourcemigration=self::$migrations[$source];
					if($sourcemigration->source==null)
					{
						echo "$source Migration has no source set and is used as a sourceMigration! That's not allowed.\n";
					}
					if($sourcemigration->source->hasID($value))
					{
						$found=true;
						$value=$sourcemigration->getDestinationIDForSourceID($value);
						break;
					}
				}
				if(!$found)
				{
					$value=null;
				}
			}
		}
		if ( $this->jointoken != null && is_array( $value ) ) {
			$value = implode( $this->jointoken, $value );
		}
		if ( $value === null ) { return; }
		if ($destination == null ) { echo "destination is empty!\n";return; }
		$destination->{$this->destinationfield} = $value;
	}
}