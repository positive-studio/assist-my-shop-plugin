<?php
/**
 * Admin Styling Settings Template
 *
 * @package Woo_AI
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

	<style>
		.ams-preset-cards {
			display: flex;
			gap: 12px;
			flex-wrap: wrap;
			margin-top: 10px;
		}
		.ams-preset-card {
			border: 1px solid #ccd0d4;
			border-radius: 8px;
			background: #fff;
			cursor: pointer;
			padding: 8px;
			width: 180px;
			text-align: left;
		}
		.ams-preset-card.is-active {
			border-color: #2271b1;
			box-shadow: 0 0 0 1px #2271b1;
		}
		.ams-preset-card strong {
			display: block;
			margin-bottom: 6px;
		}
		.ams-preset-mini {
			width: 100%;
			height: 110px;
			border-radius: 8px;
			overflow: hidden;
			border: 1px solid #dcdcde;
		}
		.ams-preset-mini-header {
			height: 26px;
			width: 100%;
		}
		.ams-preset-mini-body {
			padding: 8px;
			display: flex;
			flex-direction: column;
			gap: 6px;
			height: calc(100% - 26px);
			box-sizing: border-box;
		}
		.ams-preset-mini-bubble {
			border-radius: 10px;
			height: 16px;
		}
		.ams-preset-mini-bubble.user {
			align-self: flex-end;
			width: 64%;
		}
		.ams-preset-mini-bubble.assistant {
			align-self: flex-start;
			width: 76%;
		}
	</style>

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
		<tr>
			<th><?php _e( 'Widget Title' )?></th>
			<td>
				<?php $this->output_text_field( 'ams_chat_title' ); ?>
				<p class="description"><?php _e('Set chat widget title (visible if photo icon is not set)' ); ?></p>
			</td>
		</tr>
		</tbody>
	</table>

	<h3>Chat Widget Appearance</h3>
	<p>Click an element in the preview, choose a color, then save.</p>

	<div style="display: grid; grid-template-columns: 360px 1fr; gap: 18px; align-items: start; max-width: 1200px;">
		<div>
			<div id="ams-builder-preview-widget"
			     style="position: relative; width: 340px; height: 470px; border: 1px solid #ccd0d4; border-radius: 12px; overflow: hidden; background: #ffffff;">
				<div data-builder-target="header_gradient"
				     style="padding: 14px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
					<div data-builder-target="header_text" style="font-size: 16px; font-weight: 600; cursor: pointer;">Assist My Shop</div>
					<div style="font-size: 18px; line-height: 1;">×</div>
				</div>

				<div data-builder-target="widget_background" style="padding: 14px; height: 336px; overflow: auto; box-sizing: border-box; cursor: pointer;">
					<div style="margin-bottom: 12px;">
						<div data-builder-target="assistant_bubble_bg"
						     style="display: inline-block; max-width: 84%; border-radius: 14px; padding: 8px 10px; cursor: pointer;">
							<span data-builder-target="assistant_text" style="cursor: pointer;">Hello! How can I help you today?</span>
						</div>
					</div>
					<div style="margin-bottom: 12px; text-align: right;">
						<div data-builder-target="user_bubble_bg"
						     style="display: inline-block; max-width: 84%; border-radius: 14px; padding: 8px 10px; cursor: pointer;">
							<span data-builder-target="user_text" style="cursor: pointer;">I need black sneakers</span>
						</div>
					</div>
					<div style="margin-bottom: 12px;">
						<div data-builder-target="assistant_bubble_bg"
						     style="display: inline-block; max-width: 92%; border-radius: 14px; padding: 8px 10px; cursor: pointer;">
							<span data-builder-target="assistant_text" style="cursor: pointer;">Great choice! Here is one option:</span>
							<div data-builder-target="card_border"
							     style="margin-top: 8px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px;">
								<div style="display: flex; gap: 8px;">
									<div data-builder-target="card_thumb_bg" style="width: 52px; height: 52px; border-radius: 6px; flex-shrink: 0; cursor: pointer; outline: 1px dashed rgba(0,0,0,0.2);" title="Product Thumbnail Placeholder"></div>
									<div style="flex: 1;">
										<div data-builder-target="assistant_text" style="font-size: 12px; font-weight: 600; margin-bottom: 4px;">Running Shoe Pro</div>
										<div data-builder-target="price_text" style="font-size: 13px; font-weight: 700; margin-bottom: 6px;">$89.99</div>
										<button type="button" data-builder-target="button_bg" style="border: none; border-radius: 6px; padding: 4px 8px; font-size: 11px; cursor: pointer;">View Product</button>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div data-builder-target="meta_text" style="font-size: 11px; margin-top: 6px; cursor: pointer;">04:35 PM</div>
				</div>

				<div data-builder-target="input_area_bg"
				     style="padding: 12px; border-top: 1px solid #e0e0e0; display: flex; gap: 8px; cursor: pointer;">
					<input type="text" value="Ask me..."
					       style="flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 8px 12px; font-size: 14px;" readonly>
					<button type="button" data-builder-target="button_bg"
					        style="border: none; border-radius: 20px; padding: 8px 14px; cursor: pointer;">Send</button>
				</div>
			</div>
		</div>

		<div>
			<div class="postbox" style="padding: 14px;">
				<h3 style="margin-top: 0;">Element Color Editor</h3>
				<p style="margin-top: 0;">Selected: <strong id="ams-builder-selected-label">None</strong></p>
				<div id="ams-builder-controls"></div>
			</div>

			<div class="postbox" style="padding: 14px; margin-top: 12px;">
				<h3 style="margin-top: 0;">Presets</h3>
				<div class="ams-preset-cards">
					<button type="button" class="ams-preset-card" data-preset="light">
						<strong>Light</strong>
						<div class="ams-preset-mini">
							<div class="ams-preset-mini-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
							<div class="ams-preset-mini-body" style="background: #ffffff;">
								<div class="ams-preset-mini-bubble assistant" style="background: #f1f3f5;"></div>
								<div class="ams-preset-mini-bubble user" style="background: #764ba2;"></div>
							</div>
						</div>
					</button>
					<button type="button" class="ams-preset-card" data-preset="dark">
						<strong>Dark</strong>
						<div class="ams-preset-mini">
							<div class="ams-preset-mini-header" style="background: linear-gradient(135deg, #7b2ff7 0%, #f107a3 100%);"></div>
							<div class="ams-preset-mini-body" style="background: #16052f;">
								<div class="ams-preset-mini-bubble assistant" style="background: #22103d;"></div>
								<div class="ams-preset-mini-bubble user" style="background: #b144ff;"></div>
							</div>
						</div>
					</button>
					<button type="button" class="ams-preset-card" data-preset="warm_pastel">
						<strong>Warm Pastel</strong>
						<div class="ams-preset-mini">
							<div class="ams-preset-mini-header" style="background: linear-gradient(135deg, #d57a8c 0%, #c9875e 100%);"></div>
							<div class="ams-preset-mini-body" style="background: #fff8f4;">
								<div class="ams-preset-mini-bubble assistant" style="background: #f7e5db;"></div>
								<div class="ams-preset-mini-bubble user" style="background: #bc6b53;"></div>
							</div>
						</div>
					</button>
					<button type="button" class="ams-preset-card" data-preset="clean">
						<strong>Clean</strong>
						<div class="ams-preset-mini">
							<div class="ams-preset-mini-header" style="background: linear-gradient(135deg, #1f7ae0 0%, #24b3ff 100%);"></div>
							<div class="ams-preset-mini-body" style="background: #ffffff;">
								<div class="ams-preset-mini-bubble assistant" style="background: #f4f9ff;"></div>
								<div class="ams-preset-mini-bubble user" style="background: #1889eb;"></div>
							</div>
						</div>
					</button>
				</div>
			</div>

			<?php
			$hidden_colors = [
				'ams_widget_title_color'     => $ams_widget_title_color,
				'ams_primary_gradient_start' => $primary_gradient_start,
				'ams_primary_gradient_end'   => $primary_gradient_end,
				'ams_primary_gradient_color' => $primary_gradient_color,
				'ams_primary_color'          => $primary_color,
				'ams_primary_hover'          => $primary_hover,
				'ams_secondary_color'        => $secondary_color,
				'ams_text_primary'           => $text_primary,
				'ams_text_secondary'         => $text_secondary,
				'ams_text_light'             => $text_light,
				'ams_background'             => $background,
				'ams_background_light'       => $background_light,
				'ams_border_color'           => $border_color,
				'ams_border_light'           => $border_light,
			];
			foreach ( $hidden_colors as $name => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php endforeach; ?>
		</div>
	</div>

	<?php submit_button( 'Save Styling Settings' ); ?>
</form>
