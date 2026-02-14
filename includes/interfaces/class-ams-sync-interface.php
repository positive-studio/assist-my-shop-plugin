<?php
/**
 * AMS Sync Interface
 *
 * Defines the public contract for sync orchestration. Implementations
 * should provide lightweight orchestration methods while heavy logic
 * is delegated to helper classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface AMS_Sync_Interface {

    /** Schedule a background sync to run immediately */
    public function schedule_immediate_sync(): void;

    /** (Optional) Ensure periodic schedule exists */
    public function schedule_sync(): void;

    /** Perform a full synchronous store sync (may be heavy) */
    public function sync_store_data();

    /** Entry point used by cron to process background batches */
    public function background_sync_store_data();

    /** AJAX handler to trigger a sync now request */
    public function handle_sync_now(): void;

    /** AJAX endpoint for admin UI polling of progress */
    public function get_sync_progress(): void;

    /** Hooks for post save/update/delete events */
    public function sync_post_update( $post_id, $post, $update ): void;
    public function sync_post_delete( $post_id ): void;
}
