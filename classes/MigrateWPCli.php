<?php


namespace Palasthotel\WordPress\Migrate;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class MigrateWPCli {

	public function __construct() {
		// Login as Admin
//		$user = get_userdata( '1' );
//		wp_set_current_user( '1', $user->user_login );
//		wp_set_auth_cookie( '1' );
//		if( !is_user_logged_in() ) {
//			\WP_CLI::error( "Migration cancelled: Login failed!\n\r" );
//			exit;
//		}
//		ini_set( 'memory_limit', '-1' );
	}

	/**
	 * Lists all registered migrations.
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrate list
	 *
	 * @when after_wp_load
	 */
	public function list(){
		$migrations = ph_migrate_migrations();
		require_once dirname(__FILE__)."/../table.php";
		$table=new \Console_Table();
		$table->setHeaders(array('Migration','Imported','Available','Delta'));
		foreach ( $migrations as $key => $object ) {
			$table->addRow(array(esc_html($key),intval($object->getNumberOfImportedItems()),intval($object->getNumberOfAvailableItems()),intval($object->getDelta())));
		}
		echo $table->getTable();
	}

	/**
	 * Imports migration
	 *
	 * <migration>
	 * : Name of the migration to import.
	 *
	 * ## OPTIONS
	 *
	 * [--update]
	 * : reimports already existing items
	 *
	 * [--id-list=<int>]
	 * : imports only the given source ids (coma separated ids)
	 *
	 * [--limit=<int>]
	 * : stop after processing specified number of items
	 *
	 * [--skip=<int>]
	 * : skip first specified number of items and than start processing
	 *
	 * [--progress=<int>]
	 * : render progress after specified number of items
	 *
	 * [--log]
	 * : enables debug output
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrate import migration_name
	 *
	 * @when after_wp_load
	 */
	public function import($args, $assoc_args){
		$beginn = microtime(true);
		$migration = $args[0];
		$update = isset($assoc_args["update"]);
		$idlist = array();
		if(isset($assoc_args["id-list"])){
			$idlist = explode(",", $assoc_args["id-list"]);
		}
		$limit = (isset($assoc_args["limit"]))? intval($assoc_args["limit"]) : 0;
		$skip = (isset($assoc_args["skip"]))? intval($assoc_args["skip"]) : 0;;
		$progress = (isset($assoc_args["progress"]))? intval($assoc_args["progress"]) : -1;
		$log = isset($assoc_args["log"]);
		ph_migrate_import( $migration,$update,$idlist,$skip,$limit,$progress,$log );
		$dauer = microtime(true) - $beginn;
		echo "Migration time: $dauer seconds.\n\n";
	}

	/**
	 * Rollback a migrations
	 *
	 * <migration>
	 * : Name of the migration to rollback.
	 *
	 * ## OPTIONS
	 *
	 * [--id-list=<int>]
	 * : imports only the given source ids (coma separated ids)
	 *
	 * [--progress=<int>]
	 * : render progress after specified number of items
	 *
	 * [--log]
	 * : enables debug output
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrate rollback migration_name
	 *
	 * @when after_wp_load
	 */
	public function rollback($args, $assoc_args){
		$migration = $args[0];
		$progress = (isset($assoc_args["progress"]))? intval($assoc_args["progress"]) : -1;
		$idlist = array();
		if(isset($assoc_args["id-list"])){
			$idlist = explode(",", $assoc_args["id-list"]);
		}
		$log = isset($assoc_args["log"]);
		ph_migrate_rollback( $migration,$progress,$idlist,$log );
	}

	/**
	 * Fix images
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrate fix_images
	 *
	 * @when after_wp_load
	 */
	public function fix_images(){
		ph_migrate_fix_images();
	}

	/**
	 * Test
	 *
	 * ## EXAMPLES
	 *
	 *     wp migrate test
	 *
	 * @when after_wp_load
	 */
	public function test(){
		\WP_CLI::log("Testing migrate...");
		$hasError = false;
		$result = wp_mail("tavd@palasthotel.de", "TEST", "Should not work");
		if("I-DO-NOT-THINK" == $result){
			\WP_CLI::log("wp_mail is disabled");
		} else {
			$hasError =  true;
			\WP_CLI::error("wp_mail is not disabled", false);
		}
		$result = wp_new_user_notification(1);
		if("I-DO-NOT-THINK" == $result){
			\WP_CLI::log("wp_new_user_notification is disabled");
		} else {
			$hasError =  true;
			\WP_CLI::error("wp_new_user_notification is not disabled", false);
		}
		$result = wp_password_change_notification(null);
		if("I-DO-NOT-THINK" == $result){
			\WP_CLI::log("wp_password_change_notification is disabled");
		} else {
			$hasError =  true;
			\WP_CLI::error("wp_password_change_notification is not disabled", false);
		}

		if($hasError){
			\WP_CLI::error("There were errors...");
		}

		// TODO: check tests
		ph_migrate_statistics_init();
		ph_migrate_statistics_increment('Test',1);
		ph_migrate_statistics_increment('Test',2);
		ph_migrate_statistics_increment("Test with apostroph: '",1);

		\WP_CLI::success("All tests passed");
	}

}

\WP_CLI::add_command( 'migrate', __NAMESPACE__.'\MigrateWPCli', array(
	'shortdesc' => 'Migration framework.',
	'when' => 'before_wp_load',
) );