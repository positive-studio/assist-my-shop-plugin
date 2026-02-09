<?php
/**
 * Admin General Settings Template
 *
 * This template displays the general settings page for the Woo AI plugin.
 *
 * @package Woo_AI
 *
 * @var string   $enabled               Whether the AI assistant is enabled.
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
	<input type="hidden" name="tab" value="general">
	<table class="form-table">
		<tr>
			<th scope="row">Enable AI Assistant</th>
			<td>
				<input type="checkbox" name="enabled" value="1" <?php checked( $enabled, '1' ); ?> />
				<p class="description">Enable the AI chat widget on your store</p>
			</td>
		</tr>
		<tr>
			<th scope="row">SaaS API URL</th>
			<td>
				<input type="url" name="api_url" value="<?php echo esc_attr( $api_url ); ?>"
				       class="regular-text" required/>
				<p class="description">Your SaaS backend API URL</p>
			</td>
		</tr>
		<tr>
			<th scope="row">API Key</th>
			<td>
				<input type="text" name="api_key" value="<?php echo esc_attr( $api_key ); ?>"
				       class="regular-text" required/>
				<p class="description">Your store's API key from the SaaS dashboard</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Content Types to Sync</th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span>Content Types</span></legend>
					<?php foreach ( $available_post_types as $post_type => $post_type_object ): ?>
						<label>
							<input type="checkbox" name="post_types[]"
							       value="<?php echo esc_attr( $post_type ); ?>"
								<?php checked( in_array( $post_type, $selected_post_types ) ); ?> />
							<?php echo esc_html( $post_type_object->labels->name ); ?>
							<span style="color: #666; font-size: 12px;">(<?php echo esc_html( $post_type ); ?>)</span>
						</label><br>
					<?php endforeach; ?>
					<p class="description">Select which content types the AI should have knowledge
						about. Products are recommended for e-commerce stores.</p>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>
</form>

<h2>Data Sync Status</h2>
<p>Last sync: <?php echo get_option( 'woo_ai_last_sync', 'Never' ); ?></p>
<?php
$sync_progress = get_option( 'woo_ai_sync_progress', null );
if ( $sync_progress ) {
	$percent = $sync_progress['overall_total'] > 0 ?
		round( ( $sync_progress['overall_processed'] / $sync_progress['overall_total'] ) * 100 ) : 0;
	echo '<div class="notice notice-info inline">';
	echo '<p><strong>Background sync in progress:</strong> ' . $sync_progress['overall_processed'] . ' of ' . $sync_progress['overall_total'] . ' items (' . $percent . '%)</p>';
	if ( ! empty( $sync_progress['current_post_type'] ) ) {
		echo '<p>Currently syncing: ' . esc_html( $sync_progress['current_post_type'] ) . ' (' . $sync_progress['current_processed'] . ' of ' . $sync_progress['current_total'] . ')</p>';
	}
	echo '</div>';
}
?>
<p>
	<button type="button" class="button" onclick="syncNow()">Sync Now</button>
	<span id="sync-status"></span>
</p>