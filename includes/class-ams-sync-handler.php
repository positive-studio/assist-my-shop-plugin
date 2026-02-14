<?php

use JetBrains\PhpStorm\NoReturn;

class AMS_Sync_Handler {

	private static ?AMS_Sync_Handler $instance = null;
	private ?AMS_Api_Messenger $api_messenger = null;

	public function __construct() {
		$this->define_properties();
		$this->add_hooks();
	}

	private function define_properties(): void {
		$this->api_messenger = AMS_Api_Messenger::get();
	}

	private function add_hooks(): void {
		// Generic post type hooks for data sync
		add_action( 'save_post', [ $this, 'sync_post_update' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'sync_post_delete' ] );
		add_action( 'wp_trash_post', [ $this, 'sync_post_delete' ] );
		// Schedule periodic sync
		add_action( 'wp', [ $this, 'schedule_sync' ] );
		add_action( 'ams_sync_data', [ $this, 'sync_store_data' ] );
		add_action( 'ams_background_sync', [ $this, 'background_sync_store_data' ] );
		add_action( 'wp_ajax_ams_sync_now', [ $this, 'handle_sync_now' ] );
		add_action( 'wp_ajax_ams_get_sync_progress', [ $this, 'get_sync_progress' ] );
	}

	public function validate_api_settings(): bool {
		return ! empty( $this->api_messenger->check_api_key() );
	}

	public function sync_post_update( $post_id, $post, $update ): void {
		// Skip if this is an autosave, revision, or not a public post
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || $post->post_status !== 'publish' ) {
			return;
		}

		// Get selected post types to sync
		$selected_post_types = get_option( 'ams_post_types', [ 'product' ] );

		// Only sync if this post type is selected for sync
		if ( ! in_array( $post->post_type, $selected_post_types ) ) {
			return;
		}

		// Skip WooCommerce products as they're handled by WooCommerce hooks
		if ( $post->post_type === 'product' ) {
			return;
		}

		// Trigger a partial sync when post is updated
		wp_schedule_single_event( time() + 60, 'ams_sync_data' );
	}

	public function sync_post_delete( $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Get selected post types to sync
		$selected_post_types = get_option( 'ams_post_types', [ 'product' ] );

		// Only sync if this post type is selected for sync
		if ( ! in_array( $post->post_type, $selected_post_types ) ) {
			return;
		}

		// Send deletion notification to SaaS API
		$this->api_messenger->send_to_saas_api( '/content/delete', [
			'store_url'    => home_url(),
			'content_id'   => $post_id,
			'content_type' => $post->post_type,
		] );
	}

	public function schedule_sync() {
		if ( ! wp_next_scheduled( 'ams_sync_data' ) ) {
			//wp_schedule_event(time(), 'hourly', 'ams_sync_data');
		}
	}

	/**
	 * Schedule immediate background sync
	 */
	public function schedule_immediate_sync() {
		// Clear any existing sync jobs first
		wp_clear_scheduled_hook( 'ams_background_sync' );

		// Schedule immediate sync
		wp_schedule_single_event( time() + 5, 'ams_background_sync' );

		// Register the background sync action if not already registered
		if ( ! has_action( 'ams_background_sync', [ $this, 'background_sync_store_data' ] ) ) {
			add_action( 'ams_background_sync', [ $this, 'background_sync_store_data' ] );
		}
	}

	public function sync_store_data() {
		if ( empty( $this->api_messenger->check_api_key() ) ) {
			return false;
		}

		// Get store info
		$store_info = [
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url(),
			'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
			'version'  => defined( 'WC_VERSION' ) ? WC_VERSION : '1.0',
		];

		// Get selected post types
		$selected_post_types = get_option( 'ams_post_types', [ 'product' ] );
		$all_content_data    = [];

		foreach ( $selected_post_types as $post_type ) {
			if ( $post_type === 'product' && function_exists( 'wc_get_products' ) ) {
				// Handle WooCommerce products specially
				$content_data = $this->get_products_data();
				if ( ! empty( $content_data ) ) {
					$all_content_data['products'] = $content_data;
				}
			} else {
				// Handle other post types
				$content_data = $this->get_posts_data( $post_type );
				if ( ! empty( $content_data ) ) {
					$all_content_data[ $post_type . 's' ] = $content_data;
				}
			}
		}

		$sync_data = [
			'store_url'  => home_url(),
			'store_info' => $store_info,
		];

		// Add content data to sync payload
		$sync_data = array_merge( $sync_data, $all_content_data );

		$response = $this->api_messenger->send_to_saas_api( '/store/sync', $sync_data );

		error_log( print_r( $response, true ) );

		if ( $response && $response['success'] ) {
			update_option( 'ams_last_sync', current_time( 'mysql' ) );
		}

		return $response;
	}

	/**
	 * Get generic post data for any post type
	 */
	private function get_posts_data( $post_type ) {
		$posts = get_posts( [
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'numberposts' => - 1,
		] );

		$posts_data = [];
		foreach ( $posts as $post ) {
			$taxonomies = get_object_taxonomies( $post_type );
			$terms_data = [];

			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $post->ID, $taxonomy, [ 'fields' => 'names' ] );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$terms_data[ $taxonomy ] = $terms;
				}
			}

			$posts_data[] = [
				'id'                => $post->ID,
				'name'              => $post->post_title,
				'description'       => $post->post_content,
				'short_description' => $post->post_excerpt,
				'url'               => get_permalink( $post->ID ),
				'date_created'      => $post->post_date,
				'date_modified'     => $post->post_modified,
				'author'            => get_the_author_meta( 'display_name', $post->post_author ),
				'taxonomies'        => $terms_data,
				'image_url'         => get_the_post_thumbnail_url( $post->ID, 'full' ),
				'type'              => $post_type,
			];
		}

		return $posts_data;
	}

	/**
	 * Get WooCommerce products data
	 */
	private function get_products_data(): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [];
		}

		$products = wc_get_products( [
			'limit'  => - 1,
			'status' => 'publish',
		] );

		$products_data = [];
		foreach ( $products as $product ) {
			$products_data[] = [
				'id'                => $product->get_id(),
				'name'              => $product->get_name(),
				'description'       => $product->get_description(),
				'short_description' => $product->get_short_description(),
				'price'             => $product->get_price(),
				'regular_price'     => $product->get_regular_price(),
				'sale_price'        => $product->get_sale_price(),
				'stock_quantity'    => $product->get_stock_quantity(),
				'stock_status'      => $product->get_stock_status(),
				'sku'               => $product->get_sku(),
				'url'               => get_permalink( $product->get_id() ),
				'categories'        => wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] ),
				'tags'              => wp_get_post_terms( $product->get_id(), 'product_tag', [ 'fields' => 'names' ] ),
				'image_url'         => get_the_post_thumbnail_url( $product->get_id(), 'full' ),
				'type'              => 'product',
			];
		}

		return $products_data;
	}

	/**
	 * Get products data in batches
	 */
	private function get_products_data_batch( $limit, $offset ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [];
		}

		$products = wc_get_products( [
			'limit'  => $limit,
			'offset' => $offset,
			'status' => 'publish',
		] );

		$products_data = [];
		foreach ( $products as $product ) {
			$products_data[] = [
				'id'                => $product->get_id(),
				'name'              => $product->get_name(),
				'description'       => $product->get_description(),
				'short_description' => $product->get_short_description(),
				'price'             => $product->get_price(),
				'regular_price'     => $product->get_regular_price(),
				'sale_price'        => $product->get_sale_price(),
				'stock_quantity'    => $product->get_stock_quantity(),
				'stock_status'      => $product->get_stock_status(),
				'sku'               => $product->get_sku(),
				'url'               => get_permalink( $product->get_id() ),
				'categories'        => wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] ),
				'tags'              => wp_get_post_terms( $product->get_id(), 'product_tag', [ 'fields' => 'names' ] ),
				'image_url'         => get_the_post_thumbnail_url( $product->get_id(), 'full' ),
				'type'              => 'product',
			];
		}

		return $products_data;
	}

	/**
	 * Sync orders (smaller batch, usually not many recent orders)
	 */
	private function sync_orders_batch(): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return [];
		}

		$orders = wc_get_orders( [
			'limit'   => 50,
			'orderby' => 'date',
			'order'   => 'DESC',
		] );

		$orders_data = [];
		foreach ( $orders as $order ) {
			$orders_data[] = [
				'id'             => $order->get_id(),
				'status'         => $order->get_status(),
				'total'          => $order->get_total(),
				'date_created'   => $order->get_date_created()->date( 'Y-m-d H:i:s' ),
				'customer_email' => $order->get_billing_email(),
				'items'          => array_map( function ( $item ) {
					return [
						'name'     => $item->get_name(),
						'quantity' => $item->get_quantity(),
						'total'    => $item->get_total(),
					];
				}, $order->get_items() ),
			];
		}

		if ( ! empty( $orders_data ) ) {
			$this->api_messenger->send_to_saas_api( '/store/sync', [
				'store_url' => home_url(),
				'orders'    => $orders_data,
			] );
		}

		return $orders_data;
	}

	/**
	 * Complete sync and cleanup
	 */
	private function complete_sync(): void {
		delete_option( 'ams_sync_progress' );
		update_option( 'ams_last_sync', current_time( 'mysql' ) );

		// Log completion
		error_log( 'Ams: Background sync completed successfully' );
	}

	/**
	 * Background sync that processes data in batches
	 */
	public function background_sync_store_data() {
		if ( empty( $this->api_messenger->check_api_key() ) ) {
			return false;
		}

		// Get or initialize sync progress
		$sync_progress = get_option( 'ams_sync_progress', [
			'step'              => 'start',
			'current_post_type' => null,
			'post_types_queue'  => [],
			'current_processed' => 0,
			'current_total'     => 0,
			'overall_processed' => 0,
			'overall_total'     => 0,
			'batch_size'        => 50, // Process 50 items at a time
		] );

		switch ( $sync_progress['step'] ) {
			case 'start':
				$this->sync_store_info();
				$this->init_content_sync( $sync_progress );
				break;

			case 'content':
				$this->sync_content_batch( $sync_progress );
				break;

			case 'orders':
				$this->sync_orders_batch();
				$this->complete_sync();
				break;
		}
	}

	/**
	 * Sync store info (quick, non-blocking)
	 */
	private function sync_store_info() {
		$store_info = [
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url(),
		];

		if ( function_exists( 'WC' ) && function_exists( 'get_woocommerce_currency' ) ) {
			$store_info['version']  = WC()->version;
			$store_info['currency'] = get_woocommerce_currency();
		}

		$this->api_messenger->send_to_saas_api( '/store/sync', [
			'store_url'  => home_url(),
			'store_info' => $store_info,
		] );
	}

	/**
	 * Initialize content sync for all selected post types
	 */
	private function init_content_sync( &$sync_progress ): void {
		$selected_post_types                = get_option( 'ams_post_types', [ 'product' ] );
		$sync_progress['post_types_queue']  = $selected_post_types;
		$sync_progress['step']              = 'content';
		$sync_progress['overall_processed'] = 0;
		$sync_progress['overall_total']     = 0;

		// Calculate total items across all post types
		foreach ( $selected_post_types as $post_type ) {
			if ( $post_type === 'product' && function_exists( 'wc_get_products' ) ) {
				$count                          = wp_count_posts( 'product' );
				$sync_progress['overall_total'] += $count->publish ?? 0;
			} else {
				$count                          = wp_count_posts( $post_type );
				$sync_progress['overall_total'] += $count->publish ?? 0;
			}
		}

		// Start with first post type
		if ( ! empty( $sync_progress['post_types_queue'] ) ) {
			$sync_progress['current_post_type'] = array_shift( $sync_progress['post_types_queue'] );
			$this->init_current_post_type_sync( $sync_progress );
		}

		update_option( 'ams_sync_progress', $sync_progress );

		// Schedule next batch
		wp_schedule_single_event( time() + 2, 'ams_background_sync' );
	}

	/**
	 * Initialize sync for current post type
	 */
	private function init_current_post_type_sync( &$sync_progress ): void {
		$post_type                          = $sync_progress['current_post_type'];
		$count                              = wp_count_posts( $post_type );
		$sync_progress['current_total']     = $count->publish ?? 0;
		$sync_progress['current_processed'] = 0;
	}

	/**
	 * Sync content in batches for current post type
	 */
	private function sync_content_batch( &$sync_progress ): void {
		$current_post_type = $sync_progress['current_post_type'];
		$batch_size        = $sync_progress['batch_size'];
		$offset            = $sync_progress['current_processed'];

		if ( $current_post_type === 'product' && function_exists( 'wc_get_products' ) ) {
			// Handle WooCommerce products specially
			$content_data = $this->get_products_data_batch( $batch_size, $offset );
			$data_key     = 'products';
		} else {
			// Handle other post types
			$content_data = $this->get_posts_data_batch( $current_post_type, $batch_size, $offset );
			$data_key     = $current_post_type . 's';
		}

		if ( empty( $content_data ) ) {
			// No more items for current post type
			$this->move_to_next_post_type( $sync_progress );

			return;
		}

		// Send batch to SaaS API
		$this->api_messenger->send_to_saas_api( '/store/sync', [
			'store_url' => home_url(),
			$data_key   => $content_data,
		] );

		// Update progress
		$sync_progress['current_processed'] += count( $content_data );
		$sync_progress['overall_processed'] += count( $content_data );
		update_option( 'ams_sync_progress', $sync_progress );

		// Schedule next batch if there are more items for current post type
		if ( $sync_progress['current_processed'] < $sync_progress['current_total'] ) {
			wp_schedule_single_event( time() + 3, 'ams_background_sync' ); // 3 second delay between batches
		} else {
			// Move to next post type
			$this->move_to_next_post_type( $sync_progress );
		}
	}

	/**
	 * Move to next post type or complete content sync
	 */
	private function move_to_next_post_type( &$sync_progress ): void {
		if ( ! empty( $sync_progress['post_types_queue'] ) ) {
			// Move to next post type
			$sync_progress['current_post_type'] = array_shift( $sync_progress['post_types_queue'] );
			$this->init_current_post_type_sync( $sync_progress );
			update_option( 'ams_sync_progress', $sync_progress );
			wp_schedule_single_event( time() + 2, 'ams_background_sync' );
		} else {
			// All post types done, move to orders
			$sync_progress['step'] = 'orders';
			update_option( 'ams_sync_progress', $sync_progress );
			wp_schedule_single_event( time() + 2, 'ams_background_sync' );
		}
	}

	/**
	 * Get posts data in batches
	 */
	private function get_posts_data_batch( $post_type, $limit, $offset ): array {
		$posts = get_posts( [
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'numberposts' => $limit,
			'offset'      => $offset,
		] );

		$posts_data = [];
		foreach ( $posts as $post ) {
			$taxonomies = get_object_taxonomies( $post_type );
			$terms_data = [];

			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $post->ID, $taxonomy, [ 'fields' => 'names' ] );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$terms_data[ $taxonomy ] = $terms;
				}
			}

			$posts_data[] = [
				'id'                => $post->ID,
				'name'              => $post->post_title,
				'description'       => $post->post_content,
				'short_description' => $post->post_excerpt,
				'url'               => get_permalink( $post->ID ),
				'date_created'      => $post->post_date,
				'date_modified'     => $post->post_modified,
				'author'            => get_the_author_meta( 'display_name', $post->post_author ),
				'taxonomies'        => $terms_data,
				'image_url'         => get_the_post_thumbnail_url( $post->ID, 'full' ),
				'type'              => $post_type,
			];
		}

		return $posts_data;
	}

	private function validate_connection(): array {
		if ( empty( $this->api_messenger->check_api_key() ) ) {
			return [
				'success' => false,
				'message' => 'API key is missing or invalid',
			];
		}
		$response = $this->api_messenger->send_to_saas_api('/store/validate', [
			'store_url' => home_url(),
		] );
		if ( $response == null ) {
			return [
				'success' => false,
				'message' => 'No response from API',
			];
		}
		return [
			'success' => $response['success'] ?? false,
			'message' => $response['error'] ?? 'Unknown error',
		];
	}


	#[NoReturn]
	public function handle_sync_now(): void {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'ams_sync' ) ) {
			wp_die( json_encode( [ 'success' => false, 'message' => 'Invalid nonce' ] ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( json_encode( [ 'success' => false, 'message' => 'Insufficient permissions' ] ) );
		}

		$result = $this->validate_connection();
		if ( empty( $result ) || ! $result['success'] ) {
			wp_die( json_encode( [ 'success' => false, 'message' => 'Connection validation failed: ' . ( $result['message'] ?? 'Unknown' ) ] ) );
		}

		// Schedule the background sync properly
		$this->schedule_immediate_sync();

		wp_die( json_encode( [ 'success' => true, 'message' => 'Background sync scheduled successfully' ] ) );
	}

	/**
	 * Return current sync progress for polling in the admin UI
	 */
	public function get_sync_progress(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( json_encode( [ 'success' => false, 'message' => 'Insufficient permissions' ] ) );
		}

		$sync_progress = get_option( 'ams_sync_progress', null );
		$last_sync = get_option( 'ams_last_sync', 'Never' );

		wp_die( json_encode( [ 'success' => true, 'progress' => $sync_progress, 'last_sync' => $last_sync ] ) );
	}

	public static function init(): AMS_Sync_Handler {
		return self::get();
	}

	public static function get(): AMS_Sync_Handler {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}