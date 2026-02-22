<?php

class AMS_Admin_Settings {

    private array $global_styling_options = [];

	public function __construct() {
        $this->set_global_styling_options();

		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_ams_check_connection', [ $this, 'handle_connection_check' ] );
	}

    public function enqueue_admin_assets( string $hook_suffix ): void {
        // Enqueue the WordPress media script
        wp_enqueue_media();
        wp_enqueue_script(
            'my-custom-media-upload',
            AMS_URL . 'assets/admin/js/media-uploader.js',
            array( 'jquery' ),
            '1.0',
            true
        );

        // Enqueue admin helper script for sync UI and localize data
        wp_enqueue_script(
            'ams-admin',
            AMS_URL . 'assets/admin/js/ams-admin.js',
            array( 'jquery' ),
            '1.1.3',
            true
        );

        wp_localize_script( 'ams-admin', 'AmsAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ams_sync' ),
        ] );

        if ( 'settings_page_ai-assistant' === $hook_suffix ) {
            wp_enqueue_script(
                'ams-styling-tools',
                AMS_URL . 'assets/admin/js/ams-styling-tools.js',
                [],
                '1.0.0',
                true
            );

            wp_localize_script( 'ams-styling-tools', 'AmsStylingTools', [
                'hello_prompt' => __( 'Hello! How can I help you today?', 'assist-my-shop' ),
                'presets'      => $this->get_style_presets(),
            ] );
        }
    }

	/**
	 * Register admin menu page.
	 *
	 * Adds the Assist My Shop settings page under the WordPress Settings menu.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'options-general.php',
			'Assist My Shop Settings',
			'Assist My Shop',
			'manage_options',
			'ai-assistant',
			[ $this, 'admin_page' ]
		);
	}

	/**
	 * Render the admin settings page.
	 *
	 * Handles form submissions for general settings and styling options,
	 * displays tabbed interface for configuration, and shows sync status.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function admin_page(): void {
        // Handle form submissions with nonce and capability checks
        if ( isset( $_POST['submit'] ) ) {
            // Verify nonce (will die with message on failure)
            check_admin_referer( 'ams_save_settings', 'ams_settings_nonce' );

            // Verify user capability
            if ( ! current_user_can( 'manage_options' ) ) {
                echo '<div class="notice notice-error"><p>Insufficient permissions to save settings.</p></div>';
            } else {
                $this->handle_submit( $_POST );
            }
        }

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

		// Get current values
		$api_key             = get_option( 'ams_api_key', '' );
		$enabled             = get_option( 'ams_enabled', '1' );
		$selected_post_types = get_option( 'ams_post_types', [ 'product' ] );

		// Get available post types
		$available_post_types = get_post_types( [ 'public' => true ], 'objects' );
		// Remove attachment from the list as it's not useful for AI
		unset( $available_post_types['attachment'] );

		// Get styling options
		extract(AMS_WP_Plugin::get_style_options());

		?>
		<div class="wrap">
			<h1>Assist My Shop Settings</h1>

			<!-- Tab Navigation -->
			<nav class="nav-tab-wrapper">
				<a href="?page=ai-assistant&tab=general"
				   class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">General
					Settings</a>
				<a href="?page=ai-assistant&tab=styling"
				   class="nav-tab <?php echo $current_tab === 'styling' ? 'nav-tab-active' : ''; ?>">Chat Styling</a>
			</nav>

			<?php
            if ( $current_tab === 'general' ) {
			    include AMS_PATH . '/templates/admin/admin-general-settings.php';
            } elseif ( $current_tab === 'styling' ) {
                include AMS_PATH . '/templates/admin/admin-styling-settings.php';
			}
            ?>
		</div>

        <?php
    }

    private function output_photo_icon_field() {
        $media_id = get_option( 'ams_photo_icon' );
        $media_url = $media_id ? wp_get_attachment_url( $media_id ) : '';
        ?>
        <input type="hidden" id="ams_photo_icon" name="ams_photo_icon" value="<?php echo esc_attr( $media_id ); ?>" class="custom_media_url" />
        <img id="ams_photo_image_preview"
             src="<?php echo esc_attr( $media_url ); ?>"
             style="max-width: 100px; height: auto; display: <?php echo $media_id ? 'block' : 'none'; ?>;"
        />
        <button class="button ams_photo_media_upload">Upload Image</button>
        <button class="button ams_photo_media_remove"
                style="display: <?php echo $media_id ? 'inline-block' : 'none'; ?>;">Remove Image</button>
        <?php
    }

    private function update_styling_options( array $POST ): void {
        foreach ( $this->global_styling_options as $option_pair ) {
            $option_name     = $option_pair[0];
            $option_callback = $option_pair[1];
            if ( isset( $POST[ $option_name ] ) ) {
                update_option( $option_name, call_user_func( $option_callback, $POST[ $option_name ] ) );
            }
        }
    }

    private function update_general_options( array $POST ) {
        update_option( 'ams_api_key', sanitize_text_field( $POST['api_key'] ) );
        update_option( 'ams_enabled', isset( $POST['enabled'] ) ? '1' : '0' );
        // Always use OpenAI (ChatGPT)
        update_option( 'ams_ai_model', 'openai' );

        // Auto update option for the plugin: update WP core `auto_update_plugins` list
        $plugin_basename = plugin_basename( AMS_PATH . 'ams.php' );
        $auto_updates = (array) get_option( 'auto_update_plugins', [] );
        if ( isset( $POST['ams_auto_update'] ) ) {
            if ( ! in_array( $plugin_basename, $auto_updates, true ) ) {
                $auto_updates[] = $plugin_basename;
            }
        } else {
            $auto_updates = array_values( array_diff( $auto_updates, [ $plugin_basename ] ) );
        }
        update_option( 'auto_update_plugins', $auto_updates );

        // Handle post types selection
        $selected_post_types = isset( $POST['post_types'] )
                ? array_map( 'sanitize_text_field', $POST['post_types'] )
                : [];
        update_option( 'ams_post_types', $selected_post_types );
    }

    private function handle_submit( array $POST ): void {
        if ( isset( $POST['tab'] ) && $POST['tab'] === 'styling' ) {
            // Handle styling options
            $this->update_styling_options( $POST );

            echo '<div class="notice notice-success"><p>Styling settings saved!</p></div>';
        } else {
            // Handle general settings
            $this->update_general_options( $POST );

            echo '<div class="notice notice-success"><p>Settings saved! Store sync has been scheduled in the background.</p></div>';

            // Schedule immediate background sync instead of blocking sync
            AMS_Sync_Handler::get()->schedule_immediate_sync();
        }
    }

    private function output_text_field( string $field_name ): void {
        $field_value = esc_html( get_option( $field_name, '' ) );
        echo "<input type='text' name='$field_name' id='$field_name' value='$field_value'>";
    }

    private function set_global_styling_options(): void {
        $this->global_styling_options = [
            [ 'ams_primary_gradient_start', 'sanitize_hex_color' ],
            [ 'ams_primary_gradient_end', 'sanitize_hex_color' ],
            [ 'ams_primary_gradient_color', 'sanitize_hex_color' ],
            [ 'ams_primary_color', 'sanitize_hex_color' ],
            [ 'ams_primary_hover', 'sanitize_hex_color' ],
            [ 'ams_secondary_color', 'sanitize_hex_color' ],
            [ 'ams_text_primary', 'sanitize_hex_color' ],
            [ 'ams_text_secondary', 'sanitize_hex_color' ],
            [ 'ams_text_light', 'sanitize_hex_color' ],
            [ 'ams_background', 'sanitize_hex_color' ],
            [ 'ams_background_light', 'sanitize_hex_color' ],
            [ 'ams_border_color', 'sanitize_hex_color' ],
            [ 'ams_border_light', 'sanitize_hex_color' ],
            [ 'ams_photo_icon', 'sanitize_text_field' ],
            [ 'ams_assistant_name', 'sanitize_text_field' ],
            [ 'ams_chat_title', 'sanitize_text_field' ],
            [ 'ams_widget_title_color', 'sanitize_hex_color' ]
        ];
    }

    private function get_style_presets(): array {
        return [
            'light' => [
                'ams_widget_title_color'    => '#ffffff',
                'ams_primary_gradient_start'=> '#667eea',
                'ams_primary_gradient_end'  => '#764ba2',
                'ams_primary_gradient_color'=> '#ffffff',
                'ams_primary_color'         => '#764ba2',
                'ams_primary_hover'         => '#6769cb',
                'ams_secondary_color'       => '#6769cb',
                'ams_text_primary'          => '#333333',
                'ams_text_secondary'        => '#666666',
                'ams_text_light'            => '#999999',
                'ams_background'            => '#ffffff',
                'ams_background_light'      => '#f8f9fa',
                'ams_border_color'          => '#e0e0e0',
                'ams_border_light'          => '#dddddd',
            ],
            'dark' => [
                'ams_widget_title_color'    => '#ffffff',
                'ams_primary_gradient_start'=> '#7b2ff7',
                'ams_primary_gradient_end'  => '#f107a3',
                'ams_primary_gradient_color'=> '#fefcff',
                'ams_primary_color'         => '#b144ff',
                'ams_primary_hover'         => '#cc66ff',
                'ams_secondary_color'       => '#6f59d9',
                'ams_text_primary'          => '#efe9ff',
                'ams_text_secondary'        => '#c2b7dc',
                'ams_text_light'            => '#9788bd',
                'ams_background'            => '#16052f',
                'ams_background_light'      => '#22103d',
                'ams_border_color'          => '#5e3f8f',
                'ams_border_light'          => '#7d61b0',
            ],
            'warm_pastel' => [
                'ams_widget_title_color'    => '#ffffff',
                'ams_primary_gradient_start'=> '#d57a8c',
                'ams_primary_gradient_end'  => '#c9875e',
                'ams_primary_gradient_color'=> '#ffffff',
                'ams_primary_color'         => '#bc6b53',
                'ams_primary_hover'         => '#a95a45',
                'ams_secondary_color'       => '#b8899f',
                'ams_text_primary'          => '#2f2521',
                'ams_text_secondary'        => '#5b4b45',
                'ams_text_light'            => '#8a7a73',
                'ams_background'            => '#fff8f3',
                'ams_background_light'      => '#f7e5db',
                'ams_border_color'          => '#e2c6b8',
                'ams_border_light'          => '#edd8cd',
            ],
            'clean' => [
                'ams_widget_title_color'    => '#ffffff',
                'ams_primary_gradient_start'=> '#1f7ae0',
                'ams_primary_gradient_end'  => '#24b3ff',
                'ams_primary_gradient_color'=> '#ffffff',
                'ams_primary_color'         => '#1889eb',
                'ams_primary_hover'         => '#0f76ce',
                'ams_secondary_color'       => '#23a8f6',
                'ams_text_primary'          => '#1f2937',
                'ams_text_secondary'        => '#4b5563',
                'ams_text_light'            => '#9ca3af',
                'ams_background'            => '#ffffff',
                'ams_background_light'      => '#f4f9ff',
                'ams_border_color'          => '#d5e6f7',
                'ams_border_light'          => '#e7f0fa',
            ],
        ];
    }

    public function handle_connection_check(): void {
        check_ajax_referer( 'ams_sync', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ], 403 );
        }

        $result = AMS_Api_Messenger::get()->validate_connection();
        wp_send_json_success( [
            'connected' => ! empty( $result['success'] ),
            'message'   => $result['message'] ?? '',
        ] );
    }

}
