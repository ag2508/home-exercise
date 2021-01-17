<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link
 * @since      1.0.0
 *
 * @package    home-exercise-custom-form
 * @subpackage home-exercise-custom-form/classes
 */
class HECF
{
	/**
	 * Filepath of main plugin file.
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Absolute plugin path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Absolute plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Form entries table name.
	 *
	 * @var string
	 */
	public $hecf_db_entries_table;

	/**
	 * Constructor.
	 *
	 * @param string $file    Filepath of main plugin file
	 * @param string $version Plugin version
	 */
	public function __construct( $file, $version )
	{
		global $wpdb;

		$this->file    = $file;
		$this->version = $version;
		// Path.
		$this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
		$this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );

		$hecf_db_table_name = get_option('hecf_db_table_name');

		$this->hecf_db_entries_table = $wpdb->prefix . $hecf_db_table_name;

		if ( ! class_exists( 'WP_List_Table' ) ) {
		  	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		  	require_once $this->plugin_path . '/classes/class-HECF_Form_Entries.php'; 
		}
	}

	/**
	 * Initialize plugin hooks
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		register_activation_hook( $this->file, array( $this, 'hecf_plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'hecf_plugin_deactivation' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices') );
		add_filter( 'plugin_action_links_'.plugin_basename( $this->file ), array( $this, 'plugin_action_links' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		remove_all_filters ('wpcf7_before_send_mail');
		add_action( 'wpcf7_before_send_mail', array($this, 'hecf_save_cf7_data') );
	}

	/**
	 * The code that runs during plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function hecf_plugin_activation()
	{
		global $wpdb;
  		global $hecf_db_version;

  		$hecf_db_version = '1.0';

  		$hecf_db_table_name = $wpdb->prefix . 'hecf_form_entries';

  		if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s ", $hecf_db_table_name)) != $hecf_db_table_name) {

	  		$charset_collate = $wpdb->get_charset_collate();

	  		$hecf_db_table_name_sql = "CREATE TABLE $hecf_db_table_name (
		        id int(11) NOT NULL AUTO_INCREMENT,
		        email_address varchar(100) NOT NULL,
		        entry_values longtext NULL,
		        entry_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		        PRIMARY KEY (id)
		      ) $charset_collate;";

		    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	  		dbDelta( $hecf_db_table_name_sql );

	  		add_option( 'hecf_db_version', $hecf_db_version );

			update_option('hecf_db_table_name', 'hecf_form_entries');

			$this->hecf_db_entries_table = $wpdb->prefix . 'hecf_form_entries';
		}
	}

	/**
	 * The code that runs during plugin deactivation.
	 *
	 * @since 1.0.0
	 */
	public function hecf_plugin_deactivation()
	{
	}

	/**
	 * Load localisation files.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'home-exercise-form', false, plugin_basename( $this->plugin_path ) . '/languages' );
	}

	/**
	 * Show an Admin Notice if the Contact Form 7 plugin is not found.
	 * 
	 * @return bool
	 */
	public function admin_notices() {
		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			return false;
		}
	  	?>
	    <div class="notice notice-error is-dismissible">
			<p>
				<a href="https://wordpress.org/plugins/contact-form-7/" target="_blank"><?php esc_html_e( 'Contact Form 7', 'home-exercise-form' ); ?></a>&nbsp;<?php esc_html_e( 'is not installed or activated. You must have to install and activate', 'home-exercise-form' ); ?>&nbsp;<strong><?php esc_html_e( 'Contact Form 7', 'home-exercise-form' ); ?></strong>&nbsp;<?php esc_html_e( 'plugin to use', 'home-exercise-form' ); ?>&nbsp;<strong><?php esc_html_e( 'Home Exercise Custom Form plugin.', 'home-exercise-form' ); ?></strong>
			</p>
		</div>
	    <?php
	}

	/**
	 * Add relevant links to plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links Plugin action links
	 * 
	 * @return array Plugin action links
	 */
	public function plugin_action_links( $links )
	{
		$plugin_links = array();
		$plugin_links[] = '<a href="' .admin_url( 'options-general.php?page=hecf-entries' ) .'">' . esc_html__('Form Entries', 'home-exercise-form' ) . '</a>';
		$plugin_links[] = '<a href="' .admin_url( 'options-general.php?page=hecf-entries&settings=1' ) .'">' . esc_html__('Settings', 'home-exercise-form' ) . '</a>';
		return array_merge( $plugin_links, $links );
	}

	public function admin_menu()
	{
		$hook = add_submenu_page( 'options-general.php', __('HECF Form Entries', 'home-exercise-form' ), __('HECF Form Entries', 'home-exercise-form' ), 'manage_options', 'hecf-entries', array( $this, 'hecf_entries' ));
		add_action( "load-$hook", array( $this, 'screen_option_entry' ) );
	}

	/**
	 * Screen options for voucher list
	 */
	public function screen_option_entry()
	{
		$option = 'per_page';
		$args   = array(
			'label'   => __('Form Entries', 'home-exercise-form'),
			'default' => 20,
			'option'  => 'entries_per_page'
		);

		add_screen_option( $option, $args );

		$this->vouchers_obj = new HECF_Form_Entries();
	}

	/**
	 * Function used to show the settings page
	 */
	public function hecf_entries()
	{
		require_once $this->plugin_path . '/admin/hecf_entries_page.php'; 
	}

	public function hecf_save_cf7_data($wpcf7) 
	{
		global $wpdb;

		$obj = WPCF7_ContactForm::get_current();
		$submission = WPCF7_Submission::get_instance();
		if ($submission) {
            $submited = array();
            $submited['title'] = $wpcf7->title();
            $submited['posted_data'] = $submission->get_posted_data();
        }

        $entry_data = array();

        $posted_data = $submited['posted_data'];
        foreach ($posted_data as $key => $sdata) {
           	if (strstr($key, "-") != FALSE) {
                $key = str_replace("-", "_", $key);
            }
            $entry_data[$key] = $sdata;
        }
        
		$data = array('email_address' => $posted_data['email-address'], 'entry_values' => json_encode($entry_data));
		$wpdb->insert($this->hecf_db_entries_table, $data);

	}
}