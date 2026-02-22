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

/**
 * Contract for sync orchestration handlers.
 */
interface AMS_Sync_Interface {

    /**
     * Schedule a background sync to run immediately.
     *
     * @return void Schedules the next background sync task.
     */
    public function schedule_immediate_sync(): void;

    /**
     * Ensure periodic sync schedule exists.
     *
     * @return void Registers recurring schedule if needed.
     */
    public function schedule_sync(): void;

    /**
     * Perform a full synchronous store sync.
     *
     * @return mixed Sync response data.
     */
    public function sync_store_data();

    /**
     * Process one background sync step.
     *
     * @return mixed Background step result.
     */
    public function background_sync_store_data();

    /**
     * Handle AJAX request that triggers immediate sync.
     *
     * @return void Sends JSON response and terminates request.
     */
    public function handle_sync_now(): void;

    /**
     * Return current sync progress for admin polling.
     *
     * @return void Sends JSON response with progress details.
     */
    public function get_sync_progress(): void;

    /**
     * Handle post update event and schedule partial sync.
     *
     * @param int     $post_id Post ID being saved.
     * @param WP_Post $post    Post object instance.
     * @param bool    $update  Whether the post is an update.
     * @return void Schedules sync when conditions are met.
     */
    public function sync_post_update( $post_id, $post, $update ): void;

    /**
     * Handle post delete/trash event and notify API.
     *
     * @param int $post_id Deleted post ID.
     * @return void Sends deletion payload for selected post types.
     */
    public function sync_post_delete( $post_id ): void;
}
