<?php

class Woo_Ai_Admin_Settings {

    private array $global_styling_options = [];

	public function __construct() {
        $this->set_global_styling_options();

		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

    public function enqueue_admin_assets(): void {
        // Enqueue the WordPress media script
        wp_enqueue_media();
        wp_enqueue_script(
            'my-custom-media-upload',
            WOO_AI_URL . 'assets/admin/js/media-uploader.js',
            array( 'jquery' ),
            '1.0',
            true
        );
    }

	/**
	 * Register admin menu page.
	 *
	 * Adds the AI Assistant settings page under the WordPress Settings menu.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'options-general.php',
			'AI Assistant Settings',
			'AI Assistant',
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
		// Handle form submissions
		if ( isset( $_POST['submit'] ) ) {
            $this->handle_submit( $_POST );
		}

		// Get current tab
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';

		// Get current values
		$api_url             = get_option( 'woo_ai_api_url', 'https://your-saas-domain.com/api/v1' );
		$api_key             = get_option( 'woo_ai_api_key', '' );
		$enabled             = get_option( 'woo_ai_enabled', '1' );
		$selected_post_types = get_option( 'woo_ai_post_types', [ 'product' ] );

		// Get available post types
		$available_post_types = get_post_types( [ 'public' => true ], 'objects' );
		// Remove attachment from the list as it's not useful for AI
		unset( $available_post_types['attachment'] );

		// Get styling options
		extract(AiAssistant::get_style_options());

		?>
		<div class="wrap">
			<h1>AI Assistant Settings</h1>

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
			    include WOO_AI_PATH . '/templates/admin/admin-general-settings.php';
            } elseif ( $current_tab === 'styling' ) {
                include WOO_AI_PATH . '/templates/admin/admin-styling-settings.php';
			}
            ?>
		</div>

		<script>
            function syncNow() {
                document.getElementById('sync-status').innerHTML = 'Scheduling background sync...';
                fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=woo_ai_sync_now&nonce=<?php echo wp_create_nonce( 'woo_ai_sync' ); ?>'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('sync-status').innerHTML = 'Background sync scheduled! Refresh page to see progress.';
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            document.getElementById('sync-status').innerHTML = 'Sync failed: ' + data.message;
                        }
                    });
            }
		</script>
		<?php
	}

    private function output_photo_icon_field() {
        $media_id = get_option( 'woo_ai_photo_icon' );
        $media_url = $media_id ? wp_get_attachment_url( $media_id ) : '';
        ?>
        <input type="hidden" id="woo_ai_photo_icon" name="woo_ai_photo_icon" value="<?php echo esc_attr( $media_id ); ?>" class="custom_media_url" />
        <img id="woo_ai_photo_image_preview"
             src="<?php echo esc_attr( $media_url ); ?>"
             style="max-width: 100px; height: auto; display: <?php echo $media_id ? 'block' : 'none'; ?>;"
        />
        <button class="button woo_ai_photo_media_upload">Upload Image</button>
        <button class="button woo_ai_photo_media_remove"
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
        update_option( 'woo_ai_api_url', sanitize_text_field( $POST['api_url'] ) );
        update_option( 'woo_ai_api_key', sanitize_text_field( $POST['api_key'] ) );
        update_option( 'woo_ai_enabled', isset( $POST['enabled'] ) ? '1' : '0' );
        // Always use OpenAI (ChatGPT)
        update_option( 'woo_ai_ai_model', 'openai' );

        // Handle post types selection
        $selected_post_types = isset( $POST['post_types'] )
                ? array_map( 'sanitize_text_field', $POST['post_types'] )
                : [];
        update_option( 'woo_ai_post_types', $selected_post_types );
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
            Woo_Ai_Sync_Handler::get()->schedule_immediate_sync();
        }
    }

    private function output_text_field( string $field_name ): void {
        $field_value = esc_html( get_option( $field_name, '' ) );
        echo "<input type='text' name='$field_name' id='$field_name' value='$field_value'>";
    }

    private function set_global_styling_options(): void {
        $this->global_styling_options = [
            [ 'woo_ai_primary_gradient_start', 'sanitize_hex_color' ],
            [ 'woo_ai_primary_gradient_end', 'sanitize_hex_color' ],
            [ 'woo_ai_primary_gradient_color', 'sanitize_hex_color' ],
            [ 'woo_ai_primary_color', 'sanitize_hex_color' ],
            [ 'woo_ai_primary_hover', 'sanitize_hex_color' ],
            [ 'woo_ai_secondary_color', 'sanitize_hex_color' ],
            [ 'woo_ai_text_primary', 'sanitize_hex_color' ],
            [ 'woo_ai_text_secondary', 'sanitize_hex_color' ],
            [ 'woo_ai_text_light', 'sanitize_hex_color' ],
            [ 'woo_ai_background', 'sanitize_hex_color' ],
            [ 'woo_ai_background_light', 'sanitize_hex_color' ],
            [ 'woo_ai_border_color', 'sanitize_hex_color' ],
            [ 'woo_ai_border_light', 'sanitize_hex_color' ],
            [ 'woo_ai_photo_icon', 'sanitize_text_field' ],
            [ 'woo_ai_assistant_name', 'sanitize_text_field' ],
            [ 'woo_ai_chat_title', 'sanitize_text_field' ],
            [ 'woo_ai_widget_title_color', 'sanitize_hex_color' ]
        ];
    }

}