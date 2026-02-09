<?php
/**
 * Plugin Name: AI Chatbot
 * Description: AI-powered customer support assistant
 * Version: 1.1.3
 * Author: NR
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 *
 * @package Woo_AI_WP_Plugin
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AiAssistant
 *
 * Main plugin class that handles AI-powered customer support functionality.
 * Manages WooCommerce and WordPress content synchronization with an external
 *
 * @since 1.0.0
 */
class AiAssistant {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_classes();
		$this->define_constants();
		$this->bootstrap_frontend();
		$this->integrate_chat();
		$this->manage_sync();
		$this->add_admin_pages();
	}

	private function define_constants() {
		define( 'WOO_AI_PATH', plugin_dir_path( __FILE__ ) );
		define( 'WOO_AI_URL', plugin_dir_url( __FILE__ ) );
	}

	private function add_admin_pages(): void {
		new Woo_Ai_Admin_Settings();
	}

	/**
	 * Bootstrap frontend functionality.
	 *
	 * Enqueues frontend scripts and styles.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	private function bootstrap_frontend(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Set up data synchronization hooks.
	 *
	 * Registers AJAX handlers, WooCommerce integration, post type hooks,
	 * and scheduled sync events.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	private function manage_sync(): void {
		// Check if WooCommerce is active
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			// WooCommerce hooks for data sync
			Woo_Ai_Woocommerce_Sync::init();
		}

		Woo_Ai_Sync_Handler::init();
	}

	/**
	 * Initialize chat functionality.
	 *
	 * Instantiates the Chat_Manager class to handle chat widget integration.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	private function integrate_chat(): void {
		new Woo_Ai_Chat_Manager();
	}

	/**
	 * Register class autoloader.
	 *
	 * Sets up SPL autoloader to automatically load plugin classes from
	 * the includes directory following WordPress naming conventions.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	private function load_classes(): void {
		spl_autoload_register( function ( $class ) {
			// Common classes directory
			$class_basedir = plugin_dir_path( __FILE__ ) . 'includes' . DIRECTORY_SEPARATOR;
			// Format full class name to map corresponding file name
			$class_name_parts                = explode( '\\', $class );
			$class_name                      = count( $class_name_parts ) - 1;
			$class_name_parts[ $class_name ] = 'class-' . strtolower( $class_name_parts[ $class_name ] );
			$class_name                      = implode( '\\', $class_name_parts );
			$class_name                      = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
			$class_name                      = str_replace( '_', '-', $class_name );

			$class_filename = $class_basedir . $class_name . '.php';
			if ( file_exists( $class_filename ) ) {
				require_once $class_filename;
			}
		} );
	}

	public function enqueue_scripts() {
		if ( get_option( 'woo_ai_enabled', '1' ) !== '1' ) {
			return;
		}

		wp_enqueue_script(
			'woo-ai-chat',
			plugin_dir_url( __FILE__ ) . 'assets/chat.js',
			[ 'jquery' ],
			'1.1.3',
			true
		);

		wp_enqueue_style(
			'woo-ai-chat',
			plugin_dir_url( __FILE__ ) . 'assets/chat.css',
			[],
			'1.1.3'
		);

		wp_localize_script( 'woo-ai-chat', 'wooAi', $this->get_localize_object() );

		// Add custom CSS variables
		$this->add_custom_styles();
	}

	private function add_custom_styles() {
		// Get custom color values
		extract( self::get_style_options() );

		$custom_css = "
        :root {
            --woo-ai-chat-title-color: {$woo_ai_widget_title_color};
            --woo-ai-primary-gradient-start: {$primary_gradient_start};
            --woo-ai-primary-gradient-end: {$primary_gradient_end};
            --woo-ai-primary-gradient-coloe: {$primary_gradient_color};
            --woo-ai-primary-color: {$primary_color};
            --woo-ai-primary-hover: {$primary_hover};
            --woo-ai-secondary-color: {$secondary_color};
            --woo-ai-text-primary: {$text_primary};
            --woo-ai-text-secondary: {$text_secondary};
            --woo-ai-text-light: {$text_light};
            --woo-ai-background: {$background};
            --woo-ai-background-light: {$background_light};
            --woo-ai-border-color: {$border_color};
            --woo-ai-border-light: {$border_light};
        }";

		wp_add_inline_style( 'woo-ai-chat', $custom_css );
	}

	public static function get_style_options(): array {
		return [
			'woo_ai_widget_title_color' => get_option( 'woo_ai_widget_title_color', '#ffffff' ),
			'primary_gradient_start'    => get_option( 'woo_ai_primary_gradient_start', '#667eea' ),
			'primary_gradient_end'      => get_option( 'woo_ai_primary_gradient_end', '#764ba2' ),
			'primary_gradient_color'    => get_option( 'woo_ai_primary_gradient_color', '#ffffff' ),
			'primary_color'             => get_option( 'woo_ai_primary_color', '#764ba2' ),
			'primary_hover'             => get_option( 'woo_ai_primary_hover', '#6769cb' ),
			'secondary_color'           => get_option( 'woo_ai_secondary_color', '#6769cb' ),
			'text_primary'              => get_option( 'woo_ai_text_primary', '#333' ),
			'text_secondary'            => get_option( 'woo_ai_text_secondary', '#666' ),
			'text_light'                => get_option( 'woo_ai_text_light', '#999' ),
			'background'                => get_option( 'woo_ai_background', '#ffffff' ),
			'background_light'          => get_option( 'woo_ai_background_light', '#f8f9fa' ),
			'border_color'              => get_option( 'woo_ai_border_color', '#e0e0e0' ),
			'border_light'              => get_option( 'woo_ai_border_light', '#ddd' ),
		];
	}

	private function get_localize_object(): array {
		return [
			'wooAiAjax'     => [
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'woo_ai_chat' ),
				'store_url'         => home_url(),
				'streaming_enabled' => false, // Disabled for OpenAI, only Ollama supports streaming
			],
			'assistantName' => get_option( 'woo_ai_assistant_name', '' ),
		];
	}
}

// Initialize the plugin
new AiAssistant();