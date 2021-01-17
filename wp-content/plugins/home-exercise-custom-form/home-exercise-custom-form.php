<?php
/**
 * Plugin Name:       Home Exercise CF7 Form Entries save to DB
 * Description:       Home Exercise plugin which saves the CF7 form entries into the database table.
 * Version:           1.0.0
 * Author:            Aakash Gupta
 * Text Domain:       home-exercise-form
 * Domain Path: 	  languages
 *
 * Plugin Variable: hecf
 * 
 * @link
 * @since			1.0.0
 * @package			home-exercise-save-form-entries
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'HECF_VERSION', '1.0.0' );

/**
 * Begins execution of the plugin.
 * 
 * Return instance of HECF.
 *
 * @since   1.0.0
 * @return 	HECF
 */
function hecf()
{
	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	*/
	require_once( 'classes/class-HECF.php');

	$plugin = new HECF( __FILE__, HECF_VERSION );

	return $plugin;
}

hecf()->init();