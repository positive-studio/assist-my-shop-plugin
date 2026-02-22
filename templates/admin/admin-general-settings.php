<?php
/**
 * Admin General Settings Template
 *
 * This template displays the general settings page for the Woo AI plugin.
 *
 * @package Woo_AI
 *
 * @var string   $enabled               Whether the Assist My Shop is enabled.
 * @var string   $api_url               The SaaS backend API URL.
 * @var string   $api_key               The store's API key.
 * @var array    $available_post_types  Available post types for syncing.
 * @var array    $selected_post_types   Currently selected post types.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<form method="post" action="">
    <?php wp_nonce_field( 'ams_save_settings', 'ams_settings_nonce' ); ?>
	<input type="hidden" name="tab" value="general">
	<table class="form-table">
		<tr>
				<th scope="row"><?php esc_html_e( 'Connection Status', 'assist-my-shop' ); ?></th>
				<td>
					<span id="ams-connection-indicator" class="ams-connection-indicator"></span>
					<span id="ams-connection-status-text"><?php esc_html_e( 'Checking connection...', 'assist-my-shop' ); ?></span>
					<p class="description"><?php esc_html_e( 'Live status of plugin connection to backend API.', 'assist-my-shop' ); ?></p>
				</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Enable Assist My Shop', 'assist-my-shop' ); ?></th>
			<td>
				<input type="checkbox" name="enabled" value="1" <?php checked( $enabled, '1' ); ?> />
				<p class="description"><?php esc_html_e( 'Enable the AI chat widget on your store', 'assist-my-shop' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'API Key', 'assist-my-shop' ); ?></th>
			<td>
				<input type="text" name="api_key" value="<?php echo esc_attr( $api_key ); ?>"
				       class="regular-text" required/>
				<p class="description"><?php esc_html_e( 'Your store\'s API key from the SaaS dashboard', 'assist-my-shop' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Content Types to Sync', 'assist-my-shop' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php esc_html_e( 'Content Types', 'assist-my-shop' ); ?></span></legend>
					<?php foreach ( $available_post_types as $post_type => $post_type_object ): ?>
						<label>
								<input type="checkbox" name="post_types[]"
								       value="<?php echo esc_attr( $post_type ); ?>"
									<?php checked( in_array( $post_type, $selected_post_types ) ); ?> />
								<?php echo esc_html( $post_type_object->labels->name ); ?>
								<span class="ams-post-type-code">(<?php echo esc_html( $post_type ); ?>)</span>
							</label><br>
						<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Select which content types the AI should have knowledge about. Products are recommended for e-commerce stores.', 'assist-my-shop' ); ?></p>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>

<h2><?php esc_html_e( 'Data Sync Status', 'assist-my-shop' ); ?></h2>
<p>
	<?php
	printf(
		'%s %s',
		esc_html__( 'Last sync:', 'assist-my-shop' ),
		esc_html( get_option( 'ams_last_sync', esc_html__( 'Never', 'assist-my-shop' ) ) )
	);
	?>
</p>
<?php
$sync_progress = get_option( 'ams_sync_progress', null );
if ( $sync_progress ) {
	$overall_total     = (int) $sync_progress['overall_total'];
	$overall_processed = (int) $sync_progress['overall_processed'];
	$current_processed = (int) $sync_progress['current_processed'];
	$current_total     = (int) $sync_progress['current_total'];
	$percent           = $overall_total > 0 ? (int) round( ( $overall_processed / $overall_total ) * 100 ) : 0;

	echo '<div class="notice notice-info inline">';
	echo '<p><strong>' . esc_html__( 'Background sync in progress:', 'assist-my-shop' ) . '</strong> ';
	printf(
		esc_html__( '%1$d of %2$d items (%3$d%%)', 'assist-my-shop' ),
		$overall_processed,
		$overall_total,
		$percent
	);
	echo '</p>';

	if ( ! empty( $sync_progress['current_post_type'] ) ) {
		echo '<p>';
		printf(
			esc_html__( 'Currently syncing: %1$s (%2$d of %3$d)', 'assist-my-shop' ),
			esc_html( $sync_progress['current_post_type'] ),
			$current_processed,
			$current_total
		);
		echo '</p>';
	}
	echo '</div>';
}
?>
<p>
	<button type="button" class="button" id="ams-sync-now"><?php esc_html_e( 'Sync Now', 'assist-my-shop' ); ?></button>
		<span id="sync-status" class="ams-sync-status"></span>
	</p>

	<div id="ams-sync-progress-container" class="ams-sync-progress-container">
		<div class="ams-sync-progress-track">
			<div id="ams-sync-progress" class="ams-sync-progress-bar"></div>
		</div>
		<div id="ams-sync-progress-text" class="ams-sync-progress-text"></div>
	</div>
