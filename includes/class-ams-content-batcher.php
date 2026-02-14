<?php
/**
 * AMS Content Batcher
 *
 * Provides methods to collect content (posts, products, orders) and return
 * them in batches. This class does NOT send data to the API — it only
 * prepares payloads for the orchestrator.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AMS_Content_Batcher {

    // Default batch size for iterative loaders to avoid loading all items at once
    private const DEFAULT_BATCH_SIZE = 100;

    public function __construct() {
    }

    public function get_posts_data( string $post_type ): array {
        // Use batched loader to avoid loading all posts into memory at once.
        $all = [];
        $offset = 0;
        $batch_size = self::DEFAULT_BATCH_SIZE;

        do {
            $batch = $this->get_posts_data_batch( $post_type, $batch_size, $offset );
            if ( empty( $batch ) ) {
                break;
            }
            $all = array_merge( $all, $batch );
            $offset += count( $batch );
            // Log progress per batch
            if ( class_exists( 'AMS_Logger' ) ) {
                AMS_Logger::log( 'Processed posts batch', [ 'post_type' => $post_type, 'batch_count' => count( $batch ), 'offset' => $offset ], 'info' );
            }
            // Prevent infinite loops in case of unexpected behavior
            if ( count( $batch ) < 1 ) {
                break;
            }
        } while ( count( $batch ) === $batch_size );

        return $all;
    }

    public function get_products_data(): array {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return [];
        }

        $all = [];
        $offset = 0;
        $batch_size = self::DEFAULT_BATCH_SIZE;

        do {
            $batch = $this->get_products_data_batch( $batch_size, $offset );
            if ( empty( $batch ) ) {
                break;
            }
            $all = array_merge( $all, $batch );
            $offset += count( $batch );
            if ( class_exists( 'AMS_Logger' ) ) {
                AMS_Logger::log( 'Processed products batch', [ 'batch_count' => count( $batch ), 'offset' => $offset ], 'info' );
            }
            if ( count( $batch ) < 1 ) {
                break;
            }
        } while ( count( $batch ) === $batch_size );

        return $all;
    }

    public function get_posts_data_batch( string $post_type, int $limit, int $offset ): array {
        // Use WP_Query to fetch only IDs first to reduce memory usage
        $args = [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];

        $query = new WP_Query( $args );
        $ids = $query->posts;

        if ( empty( $ids ) ) {
            wp_reset_postdata();
            return [];
        }

        $posts_data = [];
        foreach ( $ids as $pid ) {
            $post = get_post( $pid );
            if ( ! $post ) {
                continue;
            }

            $taxonomies = get_object_taxonomies( $post_type );
            $terms_data = [];
            foreach ( $taxonomies as $taxonomy ) {
                $terms = wp_get_post_terms( $pid, $taxonomy, [ 'fields' => 'names' ] );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $terms_data[ $taxonomy ] = $terms;
                }
            }

            $thumb_id = get_post_thumbnail_id( $pid );
            $image_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';

            $author_id = get_post_field( 'post_author', $pid );
            $author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';

            $posts_data[] = [
                'id'                => $pid,
                'name'              => $post->post_title,
                'description'       => $post->post_content,
                'short_description' => $post->post_excerpt,
                'url'               => get_permalink( $pid ),
                'date_created'      => $post->post_date,
                'date_modified'     => $post->post_modified,
                'author'            => $author_name,
                'taxonomies'        => $terms_data,
                'image_url'         => $image_url,
                'type'              => $post_type,
            ];
        }

        wp_reset_postdata();
        return $posts_data;
    }

    public function get_products_data_batch( int $limit, int $offset ): array {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return [];
        }

        // Try to fetch only IDs first to reduce memory footprint. Some WC versions
        // support returning IDs via 'return' => 'ids'. If not, fall back to objects.
        $args = [
            'limit'  => $limit,
            'offset' => $offset,
            'status' => 'publish',
            'return' => 'ids',
        ];

        $maybe_ids = wc_get_products( $args );

        $product_ids = [];
        if ( empty( $maybe_ids ) ) {
            return [];
        }

        // If wc_get_products returned objects, convert to IDs
        if ( is_array( $maybe_ids ) && isset( $maybe_ids[0] ) && is_object( $maybe_ids[0] ) ) {
            foreach ( $maybe_ids as $p ) {
                if ( is_object( $p ) && method_exists( $p, 'get_id' ) ) {
                    $product_ids[] = $p->get_id();
                }
            }
        } else {
            // assume it's array of IDs
            $product_ids = (array) $maybe_ids;
        }

        $products_data = [];
        foreach ( $product_ids as $pid ) {
            $post = get_post( $pid );
            if ( ! $post ) {
                continue;
            }

            $price = get_post_meta( $pid, '_price', true );
            $regular_price = get_post_meta( $pid, '_regular_price', true );
            $sale_price = get_post_meta( $pid, '_sale_price', true );
            $stock_quantity = get_post_meta( $pid, '_stock', true );
            $stock_status = get_post_meta( $pid, '_stock_status', true );
            $sku = get_post_meta( $pid, '_sku', true );

            $thumb_id = get_post_thumbnail_id( $pid );
            $image_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';

            $products_data[] = [
                'id'                => $pid,
                'name'              => $post->post_title,
                'description'       => $post->post_content,
                'short_description' => $post->post_excerpt,
                'price'             => $price,
                'regular_price'     => $regular_price,
                'sale_price'        => $sale_price,
                'stock_quantity'    => is_numeric( $stock_quantity ) ? intval( $stock_quantity ) : null,
                'stock_status'      => $stock_status,
                'sku'               => $sku,
                'url'               => get_permalink( $pid ),
                'categories'        => wp_get_post_terms( $pid, 'product_cat', [ 'fields' => 'names' ] ),
                'tags'              => wp_get_post_terms( $pid, 'product_tag', [ 'fields' => 'names' ] ),
                'image_url'         => $image_url,
                'type'              => 'product',
            ];
        }

        return $products_data;
    }

    public function get_orders_batch( int $limit = 50 ): array {
        if ( ! function_exists( 'wc_get_orders' ) ) {
            return [];
        }

        $orders = wc_get_orders( [
            'limit'   => $limit,
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

        return $orders_data;
    }

}
