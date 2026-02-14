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

    public function __construct() {
    }

    public function get_posts_data( string $post_type ): array {
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

    public function get_products_data(): array {
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

    public function get_posts_data_batch( string $post_type, int $limit, int $offset ): array {
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

    public function get_products_data_batch( int $limit, int $offset ): array {
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
