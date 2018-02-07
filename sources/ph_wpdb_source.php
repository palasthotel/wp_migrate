<?php

class ph_wpdb_source extends ph_source
{
	private $wpdb;
	private $single_query;
	private $id_query;

	/**
	 * ph_wpdb_source constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param string $query
	 */
	public function __construct($wpdb, $id_query, $single_query)
	{
		$this->wpdb = $wpdb;
		$this->single_query = $single_query;
		$this->id_query = $id_query;

	}

	function getIDs()
	{
		return $this->wpdb->get_col($this->id_query);
	}

	function getItemByID($id)
	{
		return $this->wpdb->get_row(
				$this->wpdb->prepare($this->single_query, $id)
		);
	}


	public function describeID($id)
	{
		$row = $this->getItemByID($id);
		?>
		<p>ID: <?php echo $id; ?><br>
			Content:
			<pre>
				<?php echo esc_html( print_r($row) );?>
			</pre>
		</p>
		<?php
	}
}
