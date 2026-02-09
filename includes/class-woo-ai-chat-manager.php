<?php

class Woo_Ai_Chat_Manager {
    private ?Woo_Ai_Api_Messenger $api_messenger;
    
	public function __construct() {
        $this->api_messenger = Woo_Ai_Api_Messenger::get();
        
		add_action( 'wp_footer', [ $this, 'add_chat_widget' ] );
		add_action( 'wp_ajax_woo_ai_chat', [ $this, 'handle_chat_request' ] );
		add_action( 'wp_ajax_nopriv_woo_ai_chat', [ $this, 'handle_chat_request' ] );
		add_action( 'wp_ajax_woo_ai_chat_stream', [ $this, 'handle_chat_stream' ] );
		add_action( 'wp_ajax_nopriv_woo_ai_chat_stream', [ $this, 'handle_chat_stream' ] );
		add_action( 'wp_ajax_woo_ai_history', [ $this, 'handle_chat_history' ] );
		add_action( 'wp_ajax_nopriv_woo_ai_history', [ $this, 'handle_chat_history' ] );
	}

	public function add_chat_widget() {
		if ( get_option( 'woo_ai_enabled', '1' ) !== '1' ) {
			return;
		}

		?>
		<div id="woo-ai-chat-widget" style="display: none;">
			<div id="woo-ai-chat-toggle">
				<span>💬</span>
			</div>
			<div id="woo-ai-chat-container">
                <div id="woo-ai-chat-header">
                    <div class="woo-ai-chat-persona">
                        <?php
                        $media_id  = get_option( 'woo_ai_photo_icon' );
                        $media_url = $media_id ? wp_get_attachment_image_src( $media_id, 'thumbnail' ) : '';
                        if ( $media_url && ! empty( $media_url[0] ) ): ?>
                            <img src="<?php echo esc_url( $media_url[0] ); ?>" class="woo-ai-chat-photo">
                        <?php endif; ?>
                        <h4 class="woo-ai-chat-title"><?php echo get_option( 'woo_ai_chat_title', 'AI Assitant' ); ?></h4>
                    </div>
                    <button id="woo-ai-chat-close">&times;</button>
                </div>
				<div id="woo-ai-chat-messages"></div>
				<div id="woo-ai-chat-input-container">
					<input type="text" id="woo-ai-chat-input" placeholder="Ask me..."/>
					<button id="woo-ai-chat-send">Send</button>
				</div>
			</div>
		</div>
		<?php
	}

	public function handle_chat_request() {
		check_ajax_referer( 'woo_ai_chat', 'nonce' );

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
		check_ajax_referer( 'woo_ai_chat', 'nonce' );

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
		check_ajax_referer( 'woo_ai_chat', 'nonce' );

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