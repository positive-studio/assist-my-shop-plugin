<?php
/**
 * Admin Styling Settings Template
 *
 * This template displays the styling settings page for the Woo AI plugin,
 * allowing customization of the chat widget appearance.
 *
 * @package Woo_AI
 *
 * @var string $ams_chat_title         The chat widget title.
 * @var string $ams_widget_title_color Color for chat widget title.
 * @var string $primary_gradient_start    Start color for the gradient background.
 * @var string $primary_gradient_end      End color for the gradient background.
 * @var string $primary_gradient_color    Text color for gradient elements.
 * @var string $primary_color             Main brand color for buttons and links.
 * @var string $primary_hover             Hover state color for buttons and links.
 * @var string $secondary_color           Secondary accent color.
 * @var string $text_primary              Main text color.
 * @var string $text_secondary            Secondary text color.
 * @var string $text_light                Light text color for timestamps and metadata.
 * @var string $background                Main background color.
 * @var string $background_light          Light background color for input areas.
 * @var string $border_color              Main border color.
 * @var string $border_light              Light border color for subtle elements.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="">
	<?php wp_nonce_field( 'ams_save_settings', 'ams_settings_nonce' ); ?>
	<input type="hidden" name="tab" value="styling">
	<p>Customize the appearance of your AI chat widget to match your brand.</p>
	<h2>Chat Widget Styling</h2>
	<table class="form-table">
		<tbody>
		<tr>
			<th><h3>Assistant Persona</h3></th>
		</tr>
		<tr>
			<th><?php _e( 'Assistant Photo Icon' )?></th>
			<td>
				<?php $this->output_photo_icon_field(); ?>
				<p class="description"><?php _e( 'Set assistant photo icon, square images up to 150x150 would be the best fit.' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php _e( 'Assistant\'s Name' )?></th>
			<td>
				<?php $this->output_text_field( 'ams_assistant_name' ); ?>
				<p class="description"><?php _e( 'Set assistant\'s name for the intro chat message.' ); ?></p>
			</td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<th scope="row"><h3>Chat Widget Appearance</h3></th>
		</tr>
		<tr>
			<th scope="row">Widget Title</th>
			<td>
				<?php $this->output_text_field( 'ams_chat_title' ); ?>
				<p class="description"><?php _e('Set chat widget title (visible if photo icon is not set)' ); ?></p>
			</td>
		</tr>
        <tr>
            <th scope="row">Widget Title Color</th>
            <td>
                <input type="color" name="ams_widget_title_color"
                       value="<?php echo esc_attr( $ams_widget_title_color ); ?>"/>
                <p class="description"><?php _e('Color for chat widget title' ); ?></p>
            </td>
        </tr>
		<tr>
			<th scope="row">Primary Gradient Start</th>
			<td>
				<input type="color" name="primary_gradient_start"
				       value="<?php echo esc_attr( $primary_gradient_start ); ?>"/>
				<p class="description">Start color for the gradient background</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Primary Gradient End</th>
			<td>
				<input type="color" name="primary_gradient_end"
				       value="<?php echo esc_attr( $primary_gradient_end ); ?>"/>
				<p class="description">End color for the gradient background</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Primary Gradient Text Color</th>
			<td>
				<input type="color" name="primary_gradient_color"
				       value="<?php echo esc_attr( $primary_gradient_color ); ?>"/>
				<p class="description">Text color for gradient elements</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Primary Color</th>
			<td>
				<input type="color" name="primary_color"
				       value="<?php echo esc_attr( $primary_color ); ?>"/>
				<p class="description">Main brand color for buttons and links</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Primary Hover Color</th>
			<td>
				<input type="color" name="primary_hover"
				       value="<?php echo esc_attr( $primary_hover ); ?>"/>
				<p class="description">Hover state color for buttons and links</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Secondary Color</th>
			<td>
				<input type="color" name="secondary_color"
				       value="<?php echo esc_attr( $secondary_color ); ?>"/>
				<p class="description">Secondary accent color</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Primary Text Color</th>
			<td>
				<input type="color" name="text_primary"
				       value="<?php echo esc_attr( $text_primary ); ?>"/>
				<p class="description">Main text color</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Secondary Text Color</th>
			<td>
				<input type="color" name="text_secondary"
				       value="<?php echo esc_attr( $text_secondary ); ?>"/>
				<p class="description">Secondary text color</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Light Text Color</th>
			<td>
				<input type="color" name="text_light" value="<?php echo esc_attr( $text_light ); ?>"/>
				<p class="description">Light text color for timestamps and metadata</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Background Color</th>
			<td>
				<input type="color" name="background" value="<?php echo esc_attr( $background ); ?>"/>
				<p class="description">Main background color</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Light Background Color</th>
			<td>
				<input type="color" name="background_light"
				       value="<?php echo esc_attr( $background_light ); ?>"/>
				<p class="description">Light background color for input areas</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Border Color</th>
			<td>
				<input type="color" name="border_color"
				       value="<?php echo esc_attr( $border_color ); ?>"/>
				<p class="description">Main border color</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Light Border Color</th>
			<td>
				<input type="color" name="border_light"
				       value="<?php echo esc_attr( $border_light ); ?>"/>
				<p class="description">Light border color for subtle elements</p>
			</td>
		</tr>
		</tbody>
	</table>
	<?php submit_button( 'Save Styling Settings' ); ?>
	<button type="button" class="button" onclick="resetToDefaults()" style="margin-left: 10px;">Reset to
		Defaults
	</button>
</form>

<div class="ams-preview"
     style="margin-top: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;">
	<h3>Live Preview</h3>
	<p>Preview how your chat widget will look with the current settings:</p>
	<div id="ams-preview-widget"
	     style="position: relative; width: 300px; height: 400px; border: 1px solid #ccc; border-radius: 12px; overflow: hidden; background: var(--ams-background, #ffffff);">
		<!-- Preview content will be generated by JavaScript -->
	</div>
</div>
<?php $hello_prompt = __( 'Hello! How can I help you today?', 'assist-my-shop' ); ?>
<script>
	window.ams_i18n = window.ams_i18n || {};
	window.ams_i18n.hello_prompt = <?php echo wp_json_encode( $hello_prompt ); ?>;

    // Live preview functionality
    function updatePreview() {
        const preview = document.getElementById('ams-preview-widget');
        if (!preview) return;

        // Get current color values
        const primaryGradientStart = document.querySelector('input[name="primary_gradient_start"]').value;
        const primaryGradientEnd = document.querySelector('input[name="primary_gradient_end"]').value;
        const primaryGradientColor = document.querySelector('input[name="primary_gradient_color"]').value;
        const primaryColor = document.querySelector('input[name="primary_color"]').value;
        const primaryHover = document.querySelector('input[name="primary_hover"]').value;
        const secondaryColor = document.querySelector('input[name="secondary_color"]').value;
        const textPrimary = document.querySelector('input[name="text_primary"]').value;
        const textSecondary = document.querySelector('input[name="text_secondary"]').value;
        const textLight = document.querySelector('input[name="text_light"]').value;
        const background = document.querySelector('input[name="background"]').value;
        const backgroundLight = document.querySelector('input[name="background_light"]').value;
        const borderColor = document.querySelector('input[name="border_color"]').value;
        const borderLight = document.querySelector('input[name="border_light"]').value;

        // Update preview styles
        preview.style.setProperty('--ams-primary-gradient-start', primaryGradientStart);
        preview.style.setProperty('--ams-primary-gradient-end', primaryGradientEnd);
		preview.style.setProperty('--ams-primary-gradient-color', primaryGradientColor);
        preview.style.setProperty('--ams-primary-color', primaryColor);
        preview.style.setProperty('--ams-primary-hover', primaryHover);
        preview.style.setProperty('--ams-secondary-color', secondaryColor);
        preview.style.setProperty('--ams-text-primary', textPrimary);
        preview.style.setProperty('--ams-text-secondary', textSecondary);
        preview.style.setProperty('--ams-text-light', textLight);
        preview.style.setProperty('--ams-background', background);
        preview.style.setProperty('--ams-background-light', backgroundLight);
        preview.style.setProperty('--ams-border-color', borderColor);
        preview.style.setProperty('--ams-border-light', borderLight);

        // Update preview content
        preview.innerHTML = `
                <div style="background: linear-gradient(135deg, ${primaryGradientStart} 0%, ${primaryGradientEnd} 100%); color: ${primaryGradientColor}; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0; font-size: 16px;">Assist My Shop</h4>
                    <span style="font-size: 12px;">●</span>
                </div>
                <div style="flex: 1; padding: 16px; background: ${background}; overflow-y: auto;">
                    <div style="margin-bottom: 12px;">
                        <div style="background: ${backgroundLight}; padding: 8px 12px; border-radius: 18px; display: inline-block; max-width: 80%;">
							<p style="margin: 0; color: ${textPrimary}; font-size: 14px;">${window.ams_i18n && window.ams_i18n.hello_prompt ? window.ams_i18n.hello_prompt : 'Hello! How can I help you today?'}</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px; text-align: right;">
                        <div style="background: ${primaryColor}; color: white; padding: 8px 12px; border-radius: 18px; display: inline-block; max-width: 80%;">
                            <p style="margin: 0; font-size: 14px;">I'm looking for a hoodie</p>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <div style="background: ${backgroundLight}; padding: 8px 12px; border-radius: 18px; display: inline-block; max-width: 80%;">
                            <p style="margin: 0; color: ${textPrimary}; font-size: 14px;">Here are some great hoodies:</p>
                            <div style="margin-top: 8px; padding: 8px; border: 1px solid ${borderColor}; border-radius: 6px; background: ${background};">
                                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                    <div style="width: 40px; height: 40px; background: ${borderLight}; border-radius: 4px; margin-right: 8px;"></div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 4px 0; font-size: 12px; color: ${textPrimary};">Hoodie with Logo</h4>
                                        <p style="margin: 0 0 4px 0; font-size: 14px; font-weight: bold; color: ${primaryColor};">$45.00</p>
                                        <a href="#" style="display: inline-block; padding: 4px 8px; background: linear-gradient(135deg, ${primaryGradientStart} 0%, ${primaryGradientEnd} 100%); color: ${primaryGradientColor}; text-decoration: none; border-radius: 3px; font-size: 10px;">View Product</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding: 16px; border-top: 1px solid ${borderColor}; background: ${backgroundLight}; display: flex; gap: 8px;">
                    <input type="text" placeholder="Type your message..." style="flex: 1; padding: 8px 12px; border: 1px solid ${borderColor}; border-radius: 20px; background: ${background}; color: ${textPrimary}; font-size: 14px;" />
                    <button style="width: 40px; height: 40px; background: linear-gradient(135deg, ${primaryGradientStart} 0%, ${primaryGradientEnd} 100%); color: ${primaryGradientColor}; border: none; border-radius: 50%; cursor: pointer;">→</button>
                </div>
            `;
    }

    // Reset to default colors
    function resetToDefaults() {
        if (confirm('Are you sure you want to reset all colors to their default values?')) {
            document.querySelector('input[name="primary_gradient_start"]').value = '#667eea';
            document.querySelector('input[name="primary_gradient_end"]').value = '#764ba2';
            document.querySelector('input[name="primary_gradient_color"]').value = '#ffffff';
            document.querySelector('input[name="primary_color"]').value = '#764ba2';
            document.querySelector('input[name="primary_hover"]').value = '#6769cb';
            document.querySelector('input[name="secondary_color"]').value = '#6769cb';
            document.querySelector('input[name="text_primary"]').value = '#333';
            document.querySelector('input[name="text_secondary"]').value = '#666';
            document.querySelector('input[name="text_light"]').value = '#999';
            document.querySelector('input[name="background"]').value = '#ffffff';
            document.querySelector('input[name="background_light"]').value = '#f8f9fa';
            document.querySelector('input[name="border_color"]').value = '#e0e0e0';
            document.querySelector('input[name="border_light"]').value = '#ddd';
            updatePreview();
        }
    }

    // Add event listeners to color inputs
    document.addEventListener('DOMContentLoaded', function () {
        const colorInputs = document.querySelectorAll('input[type="color"]');
        colorInputs.forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        updatePreview(); // Initial preview
    });
</script>