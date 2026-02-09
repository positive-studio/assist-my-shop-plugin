<?php

class Woo_Ai_Api_Messenger {

	private static ?Woo_Ai_Api_Messenger $instance = null;

	/**
	 * Base URL for the SaaS API endpoint.
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	private mixed $api_base_url;

	/**
	 * API key for authenticating with the SaaS service.
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	private mixed $store_api_key;

	public function __construct() {
		$this->define_properties();
	}

	/**
	 * Define class properties from WordPress options.
	 *
	 * Retrieves API URL and API key from the database options.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function define_properties(): void {
		$this->api_base_url  = get_option( 'woo_ai_api_url', 'https://your-saas-domain.com/api/v1' );
		$this->store_api_key = get_option( 'woo_ai_api_key', '' );
	}

	public function send_to_saas_api( $endpoint, $data ) {
		if ( $data ) {
			$data['api_key'] = $this->store_api_key;
		}

		$response = wp_remote_post( $this->api_base_url . $endpoint, [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => json_encode( $data ),
			'timeout' => 120,
		] );

		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'error' => $response->get_error_message() ];
		}

		$body = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}

	public function stream_from_saas_api( $endpoint, $data ): void {
		if ( $data ) {
			$data['api_key'] = $this->store_api_key;
		}

		// Use cURL for streaming response
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $this->api_base_url . $endpoint );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: text/event-stream',
		] );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

		// Stream each chunk as it arrives and forward to client
		curl_setopt( $ch, CURLOPT_WRITEFUNCTION, function ( $ch, $data ) {
			// Forward the streaming data directly to the client
			echo $data;

			// Flush output to ensure real-time streaming
			if ( ob_get_level() ) {
				ob_flush();
			}
			flush();

			return strlen( $data );
		} );

		// Execute the streaming request
		$result = curl_exec( $ch );

		if ( curl_error( $ch ) ) {
			echo "data: " . json_encode( [ 'error' => 'Connection error: ' . curl_error( $ch ) ] ) . "\n\n";
			flush();
		}

		curl_close( $ch );
	}

	public function check_api_key(): bool {
		return ! empty( $this->store_api_key );
	}

	public static function get(): Woo_Ai_Api_Messenger {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}