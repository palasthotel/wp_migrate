<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 01.02.17
 * Time: 12:10
 */

namespace Migrate;


class MetaBox {
	function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
	}
	function add_meta_boxes( $post_type, $post){
		/**
		 * card configurator only on post type card
		 */
		add_meta_box(
			'migrate_info',
			__( 'Migrate', 'migrate' ),
			array( $this, 'render' ),
			null,
			'advanced',
			'low'
		);
	}
	function render( \WP_Post $post){
		$migrations = ph_migrate_migrations();
		$found = false;
		foreach ( $migrations as $key => $migration ) {
			
			if( !is_a( $migration->destination, "\\ph_post_destination" ) ) continue;
			
			$source_id = $migration->getSourceIDForDestinationID( $post->ID );
			if ( $source_id != null ) {
				$found = true;
				?>
				<ul>
					<li>Migration: <?php echo esc_html( $key );?></li>
					<li>Source ID: <?php echo esc_html( $source_id );?></li>
				</ul>
				<?php
				$migration->source->describeID( $source_id );
			}
		}
		if(!$found){
			echo "<p>Nothing found</p>";
		}
		?>
		<?php
	}
}