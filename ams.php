<?php
/**
 * Plugin Name: Assist My Shop
 * Plugin URI: https://assistmyshop.com
 * Description: An AI-powered customer support plugin for WooCommerce and WordPress. Provides a chat widget that integrates with your store's data to assist customers in real-time.
 * Version: 1.1.3
 * Author: Pryvus Inc.
 * Author URI: https://pryvus.com
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 *
 * @package AMS_WP
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AMS_WP_Plugin
 *
 * Main plugin class that handles AI-powered customer support functionality.
 * Manages WooCommerce and WordPress content synchronization with an external
 *
 * @since 1.0.0
 */
class AMS_WP_Plugin {

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
		define( 'AMS_PATH', plugin_dir_path( __FILE__ ) );
		define( 'AMS_URL', plugin_dir_url( __FILE__ ) );
	}

	private function add_admin_pages(): void {
		new AMS_Admin_Settings();
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
			AMS_WC_Sync::init();
		}

		AMS_Sync_Handler::init();
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
		new AMS_Chat_Manager();
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
		if ( get_option( 'ams_enabled', '1' ) !== '1' ) {
			return;
		}

		wp_enqueue_script(
			'ams-chat',
			plugin_dir_url( __FILE__ ) . 'assets/chat.js',
			[ 'jquery' ],
			'1.1.3',
			true
		);

		wp_enqueue_style(
			'ams-chat',
			plugin_dir_url( __FILE__ ) . 'assets/chat.css',
			[],
			'1.1.3'
		);

		wp_localize_script( 'ams-chat', 'Ams', $this->get_localize_object() );

		// Add custom CSS variables
		$this->add_custom_styles();
	}

	private function add_custom_styles() {
		// Get custom color values and sanitize
		$styles = self::get_style_options();

		$ams_widget_title_color   = sanitize_hex_color( $styles['ams_widget_title_color'] ?? '#ffffff' );
		$primary_gradient_start   = sanitize_hex_color( $styles['primary_gradient_start'] ?? '#667eea' );
		$primary_gradient_end     = sanitize_hex_color( $styles['primary_gradient_end'] ?? '#764ba2' );
		$primary_gradient_color   = sanitize_hex_color( $styles['primary_gradient_color'] ?? '#ffffff' );
		$primary_color            = sanitize_hex_color( $styles['primary_color'] ?? '#764ba2' );
		$primary_hover            = sanitize_hex_color( $styles['primary_hover'] ?? '#6769cb' );
		$secondary_color          = sanitize_hex_color( $styles['secondary_color'] ?? '#6769cb' );
		$text_primary             = sanitize_hex_color( $styles['text_primary'] ?? '#333' );
		$text_secondary           = sanitize_hex_color( $styles['text_secondary'] ?? '#666' );
		$text_light               = sanitize_hex_color( $styles['text_light'] ?? '#999' );
		$background               = sanitize_hex_color( $styles['background'] ?? '#ffffff' );
		$background_light         = sanitize_hex_color( $styles['background_light'] ?? '#f8f9fa' );
		$border_color             = sanitize_hex_color( $styles['border_color'] ?? '#e0e0e0' );
		$border_light             = sanitize_hex_color( $styles['border_light'] ?? '#ddd' );

		$custom_css = ":root {" .
			"--ams-chat-title-color: " . esc_attr( $ams_widget_title_color ) . ";" .
			"--ams-primary-gradient-start: " . esc_attr( $primary_gradient_start ) . ";" .
			"--ams-primary-gradient-end: " . esc_attr( $primary_gradient_end ) . ";" .
			"--ams-primary-gradient-color: " . esc_attr( $primary_gradient_color ) . ";" .
			"--ams-primary-color: " . esc_attr( $primary_color ) . ";" .
			"--ams-primary-hover: " . esc_attr( $primary_hover ) . ";" .
			"--ams-secondary-color: " . esc_attr( $secondary_color ) . ";" .
			"--ams-text-primary: " . esc_attr( $text_primary ) . ";" .
			"--ams-text-secondary: " . esc_attr( $text_secondary ) . ";" .
			"--ams-text-light: " . esc_attr( $text_light ) . ";" .
			"--ams-background: " . esc_attr( $background ) . ";" .
			"--ams-background-light: " . esc_attr( $background_light ) . ";" .
			"--ams-border-color: " . esc_attr( $border_color ) . ";" .
			"--ams-border-light: " . esc_attr( $border_light ) . ";" .
			"}";

		wp_add_inline_style( 'ams-chat', $custom_css );
	}

	public static function get_style_options(): array {
		return [
			'ams_widget_title_color' 	=> get_option( 'ams_widget_title_color', '#ffffff' ),
			'primary_gradient_start'    => get_option( 'ams_primary_gradient_start', '#667eea' ),
			'primary_gradient_end'      => get_option( 'ams_primary_gradient_end', '#764ba2' ),
			'primary_gradient_color'    => get_option( 'ams_primary_gradient_color', '#ffffff' ),
			'primary_color'             => get_option( 'ams_primary_color', '#764ba2' ),
			'primary_hover'             => get_option( 'ams_primary_hover', '#6769cb' ),
			'secondary_color'           => get_option( 'ams_secondary_color', '#6769cb' ),
			'text_primary'              => get_option( 'ams_text_primary', '#333' ),
			'text_secondary'            => get_option( 'ams_text_secondary', '#666' ),
			'text_light'                => get_option( 'ams_text_light', '#999' ),
			'background'                => get_option( 'ams_background', '#ffffff' ),
			'background_light'          => get_option( 'ams_background_light', '#f8f9fa' ),
			'border_color'              => get_option( 'ams_border_color', '#e0e0e0' ),
			'border_light'              => get_option( 'ams_border_light', '#ddd' ),
		];
	}

	private function get_localize_object(): array {
		return [
			'AmsAjax'     => [
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'ams_chat' ),
				'store_url'         => home_url(),
				'streaming_enabled' => false, // Disabled for OpenAI, only Ollama supports streaming
			],
			'assistantName' => get_option( 'ams_assistant_name', '' ),
		];
	}
}

// Initialize the plugin
new AMS_WP_Plugin();