<?php
define( 'WP_MEMORY_LIMIT','2G' );
define('DISABLE_WP_CRON', 'true');

require "do-not-event-think-about-mailing.php";

$paths = explode( 'wp-content',__FILE__ );
require_once( $paths[0] . 'wp-load.php' );

// Login as Admin
$user = get_userdata( '1' );
wp_set_current_user( '1', $user->user_login );
wp_set_auth_cookie( '1' );
if( !is_user_logged_in() ) {
  die( "Migration cancelled: Login failed!\n\r" );
}

$migrations = ph_migrate_migrations();
ini_set( 'memory_limit', '-1' );

while( 0 != ob_get_level() ) {
	ob_end_clean();
}

if ( 1 == count( $argv ) ) {
?>
Usage: migrator [operation] [parameters]

Operations:
	list - lists all registered migrations.
	import - starts an import process.
	rollback - rolls back a migration.
	
import usage:
	migrator import MIGRATION [--update] [--idList=ID,ID,ID] [--limit=LIMIT] [--progress=COUNT]
	
	--update - reimports already existing items
	--idList=ID,ID,ID - imports only the given source ids
	--limit=LIMIT - stop after processing LIMIT items
	--skip=COUNT - skip first COUNT items and than start processing
	--progress=COUNT - render progress after COUNT items
	--log - enables debug output

rollback usage:
	migrator rollback MIGRATION [--progress=COUNT] [--idList=ID,ID,ID]
	
	--progress=COUNT - render progress after COUNT items
	--idList=ID,Id,ID - rolls only the given source ids back.
	--log - enables debug output
<?php
	return;
}

if ( 'list' == $argv[1] ) {
	require('table.php');
	$table=new Console_Table();
	$table->setHeaders(array('Migration','Imported','Available','Delta'));
	foreach ( $migrations as $key => $object ) {
		$table->addRow(array(esc_html($key),intval($object->getNumberOfImportedItems()),intval($object->getNumberOfAvailableItems()),intval($object->getDelta())));
	}
	echo $table->getTable();
}

if ( 'import' == $argv[1] && count( $argv ) >= 3 ) {
  $beginn = microtime(true); 
	$migration = $argv[2];
	$update = false;
	$idlist = array();
	$limit = 0;
	$skip = 0;
	$progress = -1;
	$log = false;
	for ( $i = 3;$i < count( $argv );$i++ ) {
		if ( $argv[ $i ] == '--update' ) {
			$update = true;
		}
		if ( $argv[ $i ] == '--log' ) {
		    $log = true;
        }
		if ( 0 === strpos( $argv[ $i ], '--idList=' ) ) {
			$tmp = substr( $argv[ $i ], strlen( '--idList=' ) );
			$idlist = explode( ',', $tmp );
		}
		if ( 0 === strpos( $argv[ $i ], '--limit=' ) ) {
			$limit = intval( substr( $argv[ $i ], strlen( '--limit=' ) ) );
		}
		if ( 0 === strpos( $argv[ $i ], '--skip=' ) ) {
			$skip = intval( substr( $argv[ $i ], strlen( '--skip=' ) ) );
		}
		if ( 0 === strpos( $argv[ $i ], '--progress=' ) ) {
			$progress = intval( substr( $argv[ $i ],strlen( '--progress=' ) ) );
		}
	}
	ph_migrate_import( $migration,$update,$idlist,$skip,$limit,$progress,$log );
	$dauer = microtime(true) - $beginn; 
  echo "Migration time: $dauer seconds.\n\n";
}
if ( 'rollback' == $argv[1] && count( $argv ) >= 3 ) {
	$migration = $argv[2];
	$progress = -1;
	$idlist=array();
	$log=false;
	for ( $i = 3;$i < count( $argv );$i++ ) {
        if ( $argv[ $i ] == '--log' ) {
            $log = true;
        }
		if ( 0 === strpos( $argv[ $i ],'--progress=' ) ) {
			$progress = intval( substr( $argv[ $i ], strlen( '--progress=' ) ) );
		}
		if ( 0 === strpos( $argv[ $i ], '--idList=' ) ) {
			$tmp = substr( $argv[ $i ], strlen( '--idList=' ) );
			$idlist = explode( ',', $tmp );
		}
	}
	ph_migrate_rollback( $migration,$progress,$idlist,$log );
}

if ( 'fix_images' == $argv[1]) {
    ph_migrate_fix_images();
}

if ( 'test' == $argv[1] ) {
	ph_migrate_statistics_init();
	ph_migrate_statistics_increment('Test',1);
	ph_migrate_statistics_increment('Test',2);
	ph_migrate_statistics_increment("Test with apostroph: '",1);
}
