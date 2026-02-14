<?php

class AMS_Chat_Manager {
    private ?AMS_Api_Messenger $api_messenger;
    
	public function __construct() {
        $this->api_messenger = AMS_Api_Messenger::get();
        
		add_action( 'wp_footer', [ $this, 'add_chat_widget' ] );
		add_action( 'wp_ajax_ams_chat', [ $this, 'handle_chat_request' ] );
		add_action( 'wp_ajax_nopriv_ams_chat', [ $this, 'handle_chat_request' ] );
		add_action( 'wp_ajax_ams_chat_stream', [ $this, 'handle_chat_stream' ] );
		add_action( 'wp_ajax_nopriv_ams_chat_stream', [ $this, 'handle_chat_stream' ] );
		add_action( 'wp_ajax_ams_history', [ $this, 'handle_chat_history' ] );
		add_action( 'wp_ajax_nopriv_ams_history', [ $this, 'handle_chat_history' ] );
	}

	public function add_chat_widget() {
		if ( get_option( 'ams_enabled', '1' ) !== '1' ) {
			return;
		}

		?>
		<div id="ams-chat-widget" style="display: none;">
			<div id="ams-chat-toggle">
				<span>💬</span>
			</div>
			<div id="ams-chat-container">
                <div id="ams-chat-header">
                    <div class="ams-chat-persona">
                        <?php
                        $media_id  = get_option( 'ams_photo_icon' );
                        $media_url = $media_id ? wp_get_attachment_image_src( $media_id, 'thumbnail' ) : '';
                        if ( $media_url && ! empty( $media_url[0] ) ): ?>
                            <img src="<?php echo esc_url( $media_url[0] ); ?>" class="ams-chat-photo">
                        <?php endif; ?>
                        <h4 class="ams-chat-title"><?php echo get_option( 'ams_chat_title', 'AI Assitant' ); ?></h4>
                    </div>
                    <button id="ams-chat-close">&times;</button>
                </div>
				<div id="ams-chat-messages"></div>
				<div id="ams-chat-input-container">
					<input type="text" id="ams-chat-input" placeholder="Ask me..."/>
					<button id="ams-chat-send">Send</button>
				</div>
			</div>
		</div>
		<?php
	}

	public function handle_chat_request() {
		check_ajax_referer( 'ams_chat', 'nonce' );

		$message    = sanitize_text_field( $_POST['message'] );
		$session_id = sanitize_text_field( $_POST['session_id'] );

		if ( empty( $message ) ) {
			wp_die( json_encode( [ 'success' => false, 'error' => 'Message is required' ] ) );
		}

		$response = $this->api_messenger->send_to_saas_api( '/chat', [
			'store_url'  => home_url(),
			'message'    => $message,
			'session_id' => $session_id,
			'ai_model'   => 'openai',
		] );

		// Set proper content type and output JSON directly
		header( 'Content-Type: application/json' );
		wp_die( json_encode( $response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}

	public function handle_chat_stream() {
		check_ajax_referer( 'ams_chat', 'nonce' );

		$message    = sanitize_text_field( $_POST['message'] );
		$session_id = sanitize_text_field( $_POST['session_id'] );

		if ( empty( $message ) ) {
			header( 'Content-Type: text/event-stream' );
			echo "data: " . json_encode( [ 'error' => 'Message is required' ] ) . "\n\n";
			wp_die();
		}

		// Set headers for Server-Sent Events
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Headers: Cache-Control' );

		// Disable output buffering for real-time streaming
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Stream the response from SaaS API
		$this->api_messenger->stream_from_saas_api( '/chat/stream', [
			'store_url'  => home_url(),
			'message'    => $message,
			'session_id' => $session_id,
			'ai_model'   => 'openai',
		] );

		wp_die();
	}

	public function handle_chat_history() {
		check_ajax_referer( 'ams_chat', 'nonce' );

		$session_id = sanitize_text_field( $_POST['session_id'] );

		if ( empty( $session_id ) ) {
			wp_die( json_encode( [ 'success' => false, 'error' => 'Session ID is required' ] ) );
		}

		$response = $this->api_messenger->send_to_saas_api( '/chat/history', [
			'store_url'  => home_url(),
			'session_id' => $session_id,
		] );

		// Ensure we always have a valid response
		if ( ! $response || ! is_array( $response ) ) {
			$response = [ 'success' => false, 'error' => 'Failed to retrieve chat history' ];
		}

		// Set proper content type and output JSON directly
		header( 'Content-Type: application/json' );
		wp_die( json_encode( $response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}