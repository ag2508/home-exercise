<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;

if ( !current_user_can( 'manage_options' ) )
{
	wp_die( 'You are not allowed to be on this page.' );
}

$settings = isset($_GET['settings']) ? $_GET['settings'] : '';

if ( isset($_POST['hecf_settings_verify']) ) {
	// Check that nonce field
	wp_verify_nonce( $_POST['hecf_settings_verify'], 'hecf_settings_verify' );

	//Get form values
	$hecf_db_table_name = sanitize_text_field( $_POST['hecf_db_table_name'] );

	$old_hecf_db_table_name = get_option('hecf_db_table_name');

	if($old_hecf_db_table_name != $hecf_db_table_name) {

		$sql_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}$hecf_db_table_name SELECT * FROM {$wpdb->prefix}$old_hecf_db_table_name;";
		$query_status = $wpdb->query($sql_query);
		if($query_status) {
			$wpdb->query("ALTER TABLE {$wpdb->prefix}$hecf_db_table_name CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;");
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}$old_hecf_db_table_name" );
		}

		//Update form values to wp_options meta
		update_option('hecf_db_table_name', $hecf_db_table_name);
	}

	$settype = 'updated';
	$setmessage = __('Your Settings Saved Successfully.');
	add_settings_error(
		'hecf_settings_updated',
		esc_attr( 'settings_updated' ),
		$setmessage,
		$settype
	);
}

//Get the default or updated meta values
$hecf_db_table_name = get_option('hecf_db_table_name');
?>

<div class="wrap hecf-settings-page">
	<h1><?php echo __( 'Home Exercise Form Entries Settings' ) ?></h1><br>
	<?php settings_errors(); ?>
	<div class="nav-tab-wrapper">
		<a class="nav-tab <?php if(!$settings): ?>nav-tab-active<?php endif; ?>" href="?page=hecf-entries"><?php echo __( 'Form Entries', 'home-exercise-form' ) ?></a>
		<a class="nav-tab <?php if($settings): ?>nav-tab-active<?php endif; ?>" href="?page=hecf-entries&settings=1"><?php echo __( 'Settings', 'home-exercise-form' ) ?></a>
	</div>
	<div class="content">
		<?php if($settings): ?>
			<form method="post" name="hecf-settings" id="hecf-settings" action="<?php echo admin_url( 'options-general.php' ); ?>?page=hecf-entries&settings=1">
				<?php $hecfnonce = wp_create_nonce( 'hecf_settings_verify' ); ?>
				<input type="hidden" name="hecf_settings_verify" value="<?php echo($hecfnonce); ?>">
				<table class="form-table" id="general">
					<tbody>
						<tr>
							<th scope="row">
								<label for="hecf_db_table_name"><?php echo __( 'Database Table Name' ); ?></label>
							</th>
							<td>
								<input type="text" name="hecf_db_table_name" id="hecf_db_table_name" value="<?php echo $hecf_db_table_name; ?>" class="regular-text" aria-required="true" required>
								<p class="description"><?php esc_html_e( 'Plugin save all the form entries in this database table. You can change the table whenever you want, plugin will migrate all the entries from old table to the new table.', 'home-exercise-form' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<?php submit_button( __( 'Save Settings' ), 'primary', 'submit', false ); ?>
				</p>
			</form>
		<?php else:
			$hecf_form_entries = new HECF_Form_Entries(); ?>
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
							$hecf_form_entries->prepare_items();
							$hecf_form_entries->display(); ?>
						</form>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>