<?php

if( !defined( 'ABSPATH' ) ) exit;  // Exit if accessed directly

if ( ! class_exists( 'HECF_Form_Entries' ) ) :

/**
* HECF_Form_Entries Class
*/
class HECF_Form_Entries extends WP_List_Table
{
	/** 
	 * Class constructor
	 */
	public function __construct() 
	{
		parent::__construct( array(
			'singular' => __( 'Form Entry', 'home-exercise-form' ), //singular name of the listed records
			'plural'   => __( 'Form Entries', 'home-exercise-form' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		) );
	}

	/**
	 * Retrieve form entries data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_entries( $per_page = 20, $page_number = 1 ) 
	{
		global $wpdb;

		$hecf_db_table_name = get_option('hecf_db_table_name');

		$hecf_db_entries_table = $wpdb->prefix . $hecf_db_table_name;

		$sql = "SELECT * FROM $hecf_db_entries_table ORDER BY $hecf_db_entries_table.`id` DESC";

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	 * Delete a form entry.
	 *
	 * @param int $id entry id
	 */
	public static function delete_entry( $id ) 
	{
		global $wpdb;

		$hecf_db_table_name = get_option('hecf_db_table_name');

		$hecf_db_entries_table = $wpdb->prefix . $hecf_db_table_name;

		$wpdb->delete(
			$hecf_db_entries_table,
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() 
	{
		global $wpdb;
		$hecf_db_table_name = get_option('hecf_db_table_name');
		$hecf_db_entries_table = $wpdb->prefix . $hecf_db_table_name;

		$sql = "SELECT COUNT(*) FROM ". $hecf_db_entries_table;

		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no form entry is available */
	public function no_items() 
	{
		_e( 'No entries yet.', 'home-exercise-form' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_id
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_id ) 
	{	
		$entry_data = json_decode($item['entry_values']);
		switch ( $column_id ) {
			case 'id':
			case 'name':
				return $entry_data->first_name .' '.$entry_data->last_name;
			case 'email_address':
				return $item['email_address'];
			case 'phone_number':
				return $entry_data->phone_number;
			case 'country':
				return $entry_data->country[0];
			case 'dob':
				return $entry_data->date_of_birth;
			case 'entry_date':
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() 
	{
		$columns = array(
			'cb'      		=> '<input type="checkbox" />',
			'id'			=> __( 'Entry ID', 'home-exercise-form' ),
			'name'    		=> __( 'Name', 'home-exercise-form' ),
			'email_address'	=> __( 'Email Address', 'home-exercise-form' ),
			'phone_number'	=> __( 'Phone Number', 'home-exercise-form' ),
			'country'		=> __( 'Country', 'home-exercise-form' ),
			'dob'			=> __( 'Date of Birth', 'home-exercise-form' ),
			'entry_date'	=> __( 'Entry Date', 'home-exercise-form' ),
		);

		return $columns;
	}

	/**
	 * Render the bulk delete checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) 
	{
		return sprintf(
			'<input type="checkbox" name="entry_id[]" value="%s" />', $item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_id( $item ) 
	{
		$delete_nonce = wp_create_nonce( 'delete_enrty' );
		$title = '<strong>' . $item['id'] . '</strong>';

		return $title;
	}

	/**
	 * Method for display entry date
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_entry_date( $item )
	{
	?>
		<abbr title="<?php echo date('Y/m/d H:i:s a', strtotime($item['entry_date'])); ?>"><?php echo date('Y/m/d', strtotime($item['entry_date'])); ?></abbr>
	<?php
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions()
	{
			$actions = array(
			'bulk-delete' => __('Delete', 'home-exercise-form' )
		);

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() 
	{
		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'entries_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, 	//WE have to calculate the total number of items
			'per_page'    => $per_page 		//WE have to determine how many items to show on a page
		) );

		$this->items = self::get_entries( $per_page, $current_page );
	}

	/**
	 * Handles data for delete the bulk action
	 */
	public function process_bulk_action()
	{
		//Detect when a bulk action is being triggered...
		if ( 'bulk-delete' === $this->current_action() ) {
			foreach ($_REQUEST['entry_id'] as $entry) {
				self::delete_entry( absint( $entry ) );
			}
		        // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_safe_redirect( "?page=hecf-entries");
				exit;
		}
	}
}

endif;