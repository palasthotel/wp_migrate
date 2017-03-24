<?php
/**
 * Plugin Name: PALASTHOTEL Migrate
 * Description: Provides an migration-friendly environment.
 * Version: 1.0
 * Author: PALASTHOTEL (In Person: Enno Welbers)
 * License: GPL2
 */

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

require_once 'libs/PHP-CLI-Progress-Bar/ProgressBar/Manager.php';
require_once 'libs/PHP-CLI-Progress-Bar/ProgressBar/Registry.php';


require('destinations/ph_destination.php');
require('destinations/ph_post_destination.php');
require('destinations/ph_postmeta_destination.php');
require('destinations/ph_category_destination.php');
require('destinations/ph_user_destination.php');
require('destinations/ph_tag_destination.php');
require('destinations/ph_attachment_destination.php');
require('destinations/ph_comment_destination.php');

require('sources/ph_source.php');
require('sources/ph_xml_source.php');
require('sources/ph_xml_list_source.php');
require('sources/ph_placeholder_source.php');
require('sources/ph_json_array_source.php');
require('sources/ph_mongodb_source.php');

require('migrations/ph_field_mapping.php');
require('migrations/ph_migration.php');

require('field_handlers/post_meta_field_handler.php');
require('field_handlers/user_meta_field_handler.php');
require('field_handlers/post_attachment_field_handler.php');
require('field_handlers/post_category_field_handler.php');
require('field_handlers/category_meta_field_handler.php');

require('mappers/ph_slug_category_mapper.php');

global $ph_migrate_statistics_token;
global $ph_migrate_statistics_seen;
global $ph_migrate_log_output;

$ph_migrate_log_output=false;

function ph_migrate_log($string) {
    global $ph_migrate_log_output;
    if($ph_migrate_log_output) {
        echo $string;
    }
}

function ph_migrate_statistics_init()
{
	global $ph_migrate_statistics_token;
	global $ph_migrate_statistics_seen;
	$ph_migrate_statistics_token=time();
	$ph_migrate_statistics_seen=array();
	
	global $wpdb;
	$table_name = $wpdb->prefix.'ph_migrate_statistics';
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
	  token bigint NOT NULL,
	  title varchar(190) NOT NULL,
	  value bigint,
	  PRIMARY KEY  (token,title)
	) $charset_collate;";
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta( $sql );

}

function ph_migrate_statistics_increment($title,$delta)
{
	global $ph_migrate_statistics_token;
	global $ph_migrate_statistics_seen;
	global $wpdb;
	if(!in_array($title, $ph_migrate_statistics_seen))
	{
		$ph_migrate_statistics_seen[]=$title;
		$wpdb->insert($wpdb->prefix."ph_migrate_statistics",array('token'=>$ph_migrate_statistics_token,'title'=>$title,'value'=>$delta));
	}
	else
	{
		$wpdb->query("update ".$wpdb->prefix."ph_migrate_statistics set value=value+".$delta." where token=".$ph_migrate_statistics_token." and title='".$wpdb->_real_escape($title)."'");
	}
}

function ph_migrate_migrations()
{
	$migrations = array();
	$migrations = apply_filters( 'ph-migrations',$migrations );
	$migration_objects = array();
	foreach ( $migrations as $migration ) {
		if ( class_exists( $migration ) ) {
			$obj = new $migration();
			$migration_objects[ $obj->name ] = $obj;
		}
	}
	return $migration_objects;
}

function ph_migrate_image_editors($editors) {

    if(!class_exists('migrate_image_editor')) {
        class migrate_image_editor extends WP_Image_Editor {

            protected $path;
            public function __construct($file)
            {
                parent::__construct($file);

                $this->path=$file;
            }


            public function load()
            {
                // TODO: Implement load() method.
            }

            public function save($destfilename = null, $mime_type = null)
            {
                // TODO: Implement save() method.
            }

            public function resize($max_w, $max_h, $crop = false)
            {
                // TODO: Implement resize() method.
            }

            public function multi_resize($sizes)
            {
                $metadata=array();
                $original=getimagesize($this->path);
                $orig_width=$original[0];
                $orig_height=$original[1];
                foreach( $sizes as $size => $size_data) {
                    if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) ) {
                        continue;
                    }
                    if ( ! isset( $size_data['width'] ) ) {
                        $size_data['width'] = null;
                    }
                    if ( ! isset( $size_data['height'] ) ) {
                        $size_data['height'] = null;
                    }

                    if ( ! isset( $size_data['crop'] ) ) {
                        $size_data['crop'] = false;
                    }
                    if(image_resize_dimensions($orig_width,$orig_height,$size_data['width'],$size_data['height'],$size_data['crop'])===false) {
                    	continue;
                    }
                    $this->update_size($size_data['width'],$size_data['height']);
                    list ($filename, $extension, $mime_type) = $this->get_output_format(null,null);
                    if ( ! $filename )
                        $filename = $this->generate_filename( null, null, $extension );
                    ph_migrate_log("Dummy Generation of $filename\n");
                    if(file_exists($filename)) {
                    	unlink($filename);
                    }
                    $metadata[$size]=array(
                        'path'=>$filename,
                        'file'=>wp_basename($filename),
                        'width' => $this->size['width'],
                        'height' => $this->size['height'],
                        'mime-type'=>$mime_type,
                    );
                }
                return $metadata;
            }

            public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false)
            {
                // TODO: Implement crop() method.
            }

            public function rotate($angle)
            {
                // TODO: Implement rotate() method.
            }

            public function flip($horz, $vert)
            {
                // TODO: Implement flip() method.
            }

            public function stream($mime_type = null)
            {
                // TODO: Implement stream() method.
            }

            public static function test($args = array())
            {
                return true;
            }

            public static function supports_mime_type($mime_type)
            {
                return true;
            }

        }
    }

    return array('migrate_image_editor');

}

function ph_migrate_prevent_scheduling() {
    return false;
}

function ph_migrate_fix_images() {
    add_filter('schedule_event','ph_migrate_prevent_scheduling');
    add_filter('wp_image_editors','ph_migrate_image_editors');
    global $wpdb;
    $posts=$wpdb->get_results('select ID from '.$wpdb->posts.' where post_type=\'attachment\'');
    $progressBar = new \ProgressBar\Manager(0,count($posts));
    foreach($posts as $post) {
        $post_id=$post->ID;
        $path = get_attached_file( $post_id );
        $attach_data = wp_generate_attachment_metadata( $post_id,$path );
        wp_update_attachment_metadata( $post_id,$attach_data );
        $progressBar->advance();
    }
}

function ph_migrate_import($migration, $update, $idlist, $skip, $limit, $progress, $output_log=true)
{
    add_filter('wp_image_editors','ph_migrate_image_editors');
    add_filter('schedule_event','ph_migrate_prevent_scheduling');

    global $ph_migrate_log_output;
    $ph_migrate_log_output=$output_log;
	ph_migrate_statistics_init();
	ini_set( 'memory_limit', '-1' );
	do_action( 'ph_migrate_register_field_handlers' );
	$migrations = ph_migrate_migrations();
	$migration = $migrations[ $migration ];
	//step one: fetch the mapping table as a backing store for all migration decisions
	global $wpdb;
	$mapping = $wpdb->get_results( 'select source_id,dest_id,needs_import from '.$wpdb->prefix.'ph_migrate_map_'.$migration->name );
	$mapped_mapping=array();
	foreach($mapping as $map_entry) {
	    $mapped_mapping[$map_entry->source_id]=$map_entry;
    }

	//step two: fetch all source ids
	$ids = $migration->source->getIDs();
	//step three: apply the idlist as a filter to all source ids
	if ( is_array( $idlist ) && count( $idlist ) > 0 ) {
		$new_ids = array();
		foreach ( $ids as $id ) {
			if ( in_array( $id, $idlist ) ) {
				$new_ids[] = $id;
			}
		}
		$ids = $new_ids;
	}
	//step four: apply the skip and limit as a filter to all source ids
	if( 0 != $skip ) {
		echo "First ".$skip." items of ".count($ids)." were skipped!\n";
		$ids = array_slice( $ids, $skip );
	}
	if ( 0 != $limit ) {
		$ids = array_splice( $ids, 0, $limit );
	}
	ph_migrate_statistics_increment("Sources to be migrated",count($ids));
    $progressBar=new \ProgressBar\Manager(0,count($ids));
	//step five: for each source id check wether migration is neccessary:
	//           * using update
	//           * checking wether it's already migrated
	//           * calling prepareRow (returning NULL cancels this item)
	//           if migration should be done:
	//           * load existing entity if available
	//           * map fields over
	//           * save entity
	$nImported = 0;
	$nIgnored = 0;
	foreach ( $ids as $source_id ) {
		ph_migrate_statistics_increment("Sources analyzed",1);
        ph_migrate_log("\nID: ".$source_id."\n");
		ini_set( 'memory_limit', '-1' );
		$entry = null;
		if(isset($mapped_mapping[$source_id])) {
		    $entry=$mapped_mapping[$source_id];
        }
		if ( $entry == null || true == $entry->needs_import || $entry->dest_id == null || ($entry->dest_id != null && $update) ) {
			if ( $entry == null ) {
				$entry = new stdClass();
				$entry->source_id = $source_id;
				$entry->dest_id = null;
				$entry->needs_import = true;
				$entry->is_new = true;
				$mapping[]=$entry;
				$mapped_mapping[$entry->source_id]=$entry;
			}
			$data = $migration->source->getItemByID( $source_id );
			$data = $migration->prepareRow( $data );
			if ( $data != null ) {
				$destination = null;
				if ( $entry->dest_id != null ) {
                    ph_migrate_log("\nfetching destination...".$entry->dest_id."\n");
					$destination = $migration->destination->getItemByID( $entry->dest_id );
				}
				else
				{
                    ph_migrate_log("\ncreating destination...\n");
					$destination = $migration->destination->createItem();
				}
				if($destination==null)
				{
                    ph_migrate_log("\nunable to get destination!\n");
					if($migration->destination==null)
					{
                        ph_migrate_log("migration destination is null!\n");
					}
				}
				else
				{
					$migration->map( $data,$destination );
					$dest_id = $migration->destination->save( $destination );
					// if $dest_id wp_error -> abfangen
					$entry->dest_id = $dest_id;
					$entry->needs_import = false;
					if ( isset($entry->is_new) ) {
						if($wpdb->insert( $wpdb->prefix.'ph_migrate_map_'.$migration->name, array( 'source_id' => $entry->source_id, 'dest_id' => $entry->dest_id, 'needs_import' => $entry->needs_import ) )===false) {
							//this happens whenever we have generated a stub and are filliing it out on the same run. Self-referencing migrations...
                            ph_migrate_log("\nwhoops. Updating.\n");
							$wpdb->update( $wpdb->prefix.'ph_migrate_map_'.$migration->name,array( 'source_id' => $entry->source_id, 'dest_id' => $entry->dest_id, 'needs_import' => $entry->needs_import ),array( 'source_id' => $entry->source_id ) );
						}
					}
					else
					{
						$wpdb->update( $wpdb->prefix.'ph_migrate_map_'.$migration->name,array( 'source_id' => $entry->source_id, 'dest_id' => $entry->dest_id, 'needs_import' => $entry->needs_import ),array( 'source_id' => $entry->source_id ) );
					}
					$nImported++;
					ph_migrate_statistics_increment("Sources imported or updated",1);
				}

			}
			else
			{
				$nIgnored++;
				ph_migrate_statistics_increment("Sources ignored",1);
			}
		}
		else
		{
			$nIgnored++;
			ph_migrate_statistics_increment("Sources ignored",1);
		}
		if ( 0 == (($nImported + $nIgnored) % $progress) && $progress != -1 ) {
			echo esc_html( "\nMigration progress: $nImported imported, $nIgnored skipped, ".(count( $ids ) -($nImported + $nIgnored))." remaining.\n" );
		}
        $progressBar->advance();
	}
	//step six: PROFIT
	echo esc_html( "\nMigration result: $nImported imported, $nIgnored skipped.\n" );
}

function ph_migrate_delete($migration)
{
	ini_set( 'memory_limit', '-1' );

	$migrations = ph_migrate_migrations();
	$migration = $migrations[ $migration ];
	//step one: fetch the mapping table as a backing store for all migration decisions
	global $wpdb;
	$mapping = $wpdb->get_results( 'select source_id,dest_id,needs_import from '.$wpdb->prefix.'ph_migrate_map_'.$migration->name );
	$nDeleted = 0;
	$idList=$migration->source->getIDs();
	foreach($mapping as $item)
	{
		if( $item->dest_id != null && !in_array($item->source_id, $idList) )
		{
			$dest = $migration->destination->getItemByID( $item->dest_id );
			if( $dest != null )
			{
				echo "checking ".$item->dest_id."\n";
				if( method_exists($migration->destination, "isItemDeletable") )
				{
					if( $migration->destination->isItemDeletable( $dest ) )
					{
						echo "Would delete: ".$item->dest_id."\n";
						$migration->destination->deleteItem( $dest );
						$wpdb->delete($wpdb->prefix.'ph_migrate_map_'.$migration->name,array(source_id=>$item->source_id));
						$nDeleted++;
					}
				}
				else
				{
					$migration->destination->deleteItem( $dest );
					$wpdb->delete($wpdb->prefix.'ph_migrate_map_'.$migration->name,array(source_id=>$item->source_id));
					$nDeleted++;
				}
			}
		}
	}
	echo esc_html( "Deletion result: $nDeleted deleted.\n" );	
}

function ph_migrate_rollback($migration, $progress,$idlist,$output_log=true)
{
    global $ph_migrate_log_output;
    $ph_migrate_log_output=$output_log;
	ini_set( 'memory_limit', '-1' );

	$migrations = ph_migrate_migrations();
	$migration = $migrations[ $migration ];
	//step one: fetch the mapping table as a backing store for all migration decisions
	global $wpdb;
	$mapping = $wpdb->get_results( 'select source_id,dest_id,needs_import from '.$wpdb->prefix.'ph_migrate_map_'.$migration->name );
	$nDeleted = 0;
	$progressBar=new \ProgressBar\Manager(0,count($mapping));
	foreach ( $mapping as $item ) {
		ini_set( 'memory_limit', '-1' );
		if ( $item->dest_id != null && (count($idlist)==0 || in_array($item->source_id, $idlist)) ) {
			$dest = $migration->destination->getItemByID( $item->dest_id );
			if ( $dest != null ) {
				$migration->destination->deleteItem( $dest );
				$nDeleted++;
				if ( 0 == $nDeleted % $progress && $progress != -1 ) {
					echo esc_html( "\nRollback progress: $nDeleted deleted, ".(count( $mapping ) -$nDeleted)." remaining.\n" );
				}
			}
			$wpdb->query( 'update '.$wpdb->prefix.'ph_migrate_map_'.$migration->name." set dest_id=null, needs_import=null where source_id='".$item->source_id."'" );
		}
		$progressBar->advance();
	}
	echo esc_html( "\nRollback result: $nDeleted deleted.\n" );
}

$ph_migrate_field_handlers = array();

function ph_migrate_register_field_handler($destination_class, $prefix, $callback)
{
	global $ph_migrate_field_handlers;
	$ph_migrate_field_handlers[ $destination_class ][ $prefix ] = $callback;
}

function ph_migrate_get_field_handlers($class_object){
	global $ph_migrate_field_handlers;
	$field_handlers = array();
	$class=get_class( $class_object );
	while( FALSE != $class )
	{
		if( isset( $ph_migrate_field_handlers[ $class ] ) )
		{
			$field_handlers=array_merge( $field_handlers, $ph_migrate_field_handlers[ $class ] );
		}
		$class = get_parent_class( $class );
	}
	return $field_handlers;
}

/**
 * collect process callbacks from field handles and store unhandled properties
 *
 * @param $migration_item object
 * @param $field_handlers array
 * @param $rest object unhandled properties go to rest object
 *
 * @return array
 */
function ph_migrate_get_process($migration_item, $field_handlers, &$rest){
	$process = array();
	foreach ( $migration_item as $property => $value ) {
		$handled = false;
		foreach ( $field_handlers as $key => $callback ) {
			if ( 0 === strpos( $property,$key ) ) {
				$handled = true;
				if ( ! isset($process[ $key ]) ) {
					$process[ $key ] = array( 'callback' => $callback, 'fields' => array() );
				}
				$process[ $key ]['fields'][ $property ] = $value;
			}
		}
		if ( ! $handled ) {
			$rest->{$property} = $value;
		}
	}
	return $process;
}

function ph_migrate_admin_menu()
{
	add_submenu_page( 'tools.php','Migrate Lookup','Migrate Lookup','edit_posts','migrate_lookup','ph_migrate_lookup' );
	add_submenu_page( 'tools.php','Migrate Stats','Migrate Stats','edit_posts','migrate_stats','ph_migrate_stats' );
}
add_action( 'admin_menu','ph_migrate_admin_menu' );

function ph_migrate_stats()
{
	global $wpdb;
	if(isset($_GET['token']))
	{
		$data=$wpdb->get_results("select token,title,value from ".$wpdb->prefix."ph_migrate_statistics where token=".$_GET['token']." order by title ASC",OBJECT);
?>
<h2>Migrate statistics (<?php echo date('d.m.Y h:i:s',$_GET['token']);?>)</h2>
<ul>
<?php
	foreach($data as $row)
	{
?>
	<li><?php echo $row->title?>: <?php echo $row->value; ?></li>
<?php
	}
?>
</ul>
<a href="<?php echo add_query_arg(array('page'=>'migrate_stats'),admin_url('tools.php'));?>">Zurück</a>
<?php
	}
	else
	{
		$data=$wpdb->get_results('select distinct token from '.$wpdb->prefix.'ph_migrate_statistics order by token DESC',OBJECT);
?>
<h2>Migrate statistics</h2>
<ul>
<?php
		foreach($data as $row)
		{
?>
	<li><a href="<?php echo add_query_arg(array('token'=>$row->token,'page'=>'migrate_stats'),admin_url('tools.php'));?>"><?php echo date('d.m.Y h:i:s',$row->token); ?></a></li>
<?php
		}
?>
</ul>
<?php
	}
}

function ph_migrate_lookup()
{
?>
<h1>Migrate Lookup</h1>
<?php
	$migrations = ph_migrate_migrations();

if ( isset($_POST) ) {
	$input = array();
	if ( isset( $_POST['post_id'] ) ) {
		$input['post_id'] = esc_html( $_POST['post_id'] );
	}
	if ( isset( $_POST['migration'] ) ) {
		$input['migration'] = esc_html( $_POST['migration'] );
	}
	if ( isset( $_POST['source_id'] ) ) {
		$input['source_id'] = esc_html( $_POST['source_id'] );
	}
	if ( isset($input['post_id']) ) {
		foreach ( $migrations as $key => $migration ) {
			$source_id = $migration->getSourceIDForDestinationID( $input['post_id'] );
			if ( $source_id != null ) {
?>
<ul>
<li>Migration: <?php echo esc_html( $key );?></li>
<li>Source ID: <?php echo esc_html( $source_id );?></li>
<li>Post ID: <?php echo esc_html( edit_post_link( $input['post_id'], "", "", $input['post_id'] ) );?></li>
</ul>
<?php
				$migration->source->describeID( $source_id );
			}
		}
	}
	else if ( isset($input['source_id']) ) {
		$migration = $migrations[ $input['migration'] ];
		$dest_id = $migration->getDestinationIDForSourceID( $input['source_id'],false );
?>
<ul>
<li>Migration: <?php echo esc_html( $input['migration'] );?></li>
<li>Source ID: <?php echo esc_html( $input['source_id'] );?></li>
<li>Post ID: <?php echo esc_html( edit_post_link( $dest_id, "", "", $dest_id ) );?></li>
<?php
		$migration->source->describeID( $input['source_id'] );
	}
}
?>
<div>
	<h2>Lookup by Post ID</h2>
	<form method="POST">
	<input type="number" name="post_id"/>
	<input type="submit" value="Lookup"/>
	</form>
</div>
<div>
	<h2>Lookup by Migration and Source ID</h2>
	<form method="POST">
	<p>
		Source ID: <input type="text" name="source_id"/>
	</p>
	<p>
		Migration: <select name="migration">
<?php

foreach ( $migrations as $key => $value ) {
?>
	<option value="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $key );?></option>
<?php
}
?>
		</select>
	</p>
	<input type="submit" value="Lookup"/>
	</form>
</div>
<?php
}

// Meta box
require_once "meta-box.php";
new \Migrate\MetaBox();
