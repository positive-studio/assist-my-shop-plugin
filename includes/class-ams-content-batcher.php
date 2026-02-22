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

/**
 * Content extraction helper that builds sync payload batches.
 */
class AMS_Content_Batcher {

    // Default batch size for iterative loaders to avoid loading all items at once
    private const DEFAULT_BATCH_SIZE = 100;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
    }

    /**
     * Collect all posts for a post type in batches.
     *
     * @param string $post_type Post type slug.
     * @return array<int, array<string, mixed>> Aggregated post payloads.
     */
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

    /**
     * Collect all WooCommerce products in batches.
     *
     * @return array<int, array<string, mixed>> Aggregated product payloads.
     */
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

    /**
     * Fetch one batch of posts for a post type.
     *
     * @param string $post_type Post type slug.
     * @param int    $limit     Maximum number of posts to return.
     * @param int    $offset    Query offset.
     * @return array<int, array<string, mixed>> Post payload batch.
     */
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

    /**
     * Fetch one batch of WooCommerce products.
     *
     * @param int $limit  Maximum number of products to return.
     * @param int $offset Query offset.
     * @return array<int, array<string, mixed>> Product payload batch.
     */
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
            $attributes = $this->get_product_attributes_data( $pid );

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
                'attributes'        => $attributes,
                'image_url'         => $image_url,
                'type'              => 'product',
            ];
        }

        return $products_data;
    }

    /**
     * Return all WooCommerce product attributes (global + custom) in a normalized format.
     *
     * @param int $product_id WooCommerce product ID.
     * @return array<int, array<string, mixed>> Normalized attribute payload.
     */
    private function get_product_attributes_data( int $product_id ): array {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return [];
        }

        $product = wc_get_product( $product_id );
        if ( ! $product || ! method_exists( $product, 'get_attributes' ) ) {
            return [];
        }

        $attributes = $product->get_attributes();
        if ( empty( $attributes ) || ! is_array( $attributes ) ) {
            return [];
        }

        $normalized = [];

        foreach ( $attributes as $key => $attribute ) {
            if ( ! is_object( $attribute ) || ! method_exists( $attribute, 'get_name' ) ) {
                continue;
            }

            $taxonomy_name = $attribute->get_name();
            $is_taxonomy = method_exists( $attribute, 'is_taxonomy' ) ? (bool) $attribute->is_taxonomy() : false;

            $options = [];
            if ( $is_taxonomy ) {
                $terms = wc_get_product_terms( $product_id, $taxonomy_name, [ 'fields' => 'names' ] );
                if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
                    $options = array_values( array_filter( $terms, static function ( $value ) {
                        return $value !== null && $value !== '';
                    } ) );
                }
            } elseif ( method_exists( $attribute, 'get_options' ) ) {
                $raw_options = $attribute->get_options();
                if ( is_array( $raw_options ) ) {
                    $options = array_values( array_filter( array_map( static function ( $value ) {
                        return is_scalar( $value ) ? (string) $value : '';
                    }, $raw_options ), static function ( $value ) {
                        return $value !== '';
                    } ) );
                }
            }

            $normalized[] = [
                'name'      => (string) $taxonomy_name,
                'label'     => function_exists( 'wc_attribute_label' ) ? (string) wc_attribute_label( $taxonomy_name ) : (string) $taxonomy_name,
                'options'   => $options,
                'visible'   => method_exists( $attribute, 'get_visible' ) ? (bool) $attribute->get_visible() : true,
                'variation' => method_exists( $attribute, 'get_variation' ) ? (bool) $attribute->get_variation() : false,
                'position'  => method_exists( $attribute, 'get_position' ) ? (int) $attribute->get_position() : 0,
                'source'    => $is_taxonomy ? 'taxonomy' : 'custom',
            ];
        }

        return $normalized;
    }

    /**
     * Fetch the latest WooCommerce orders for sync payload.
     *
     * @param int $limit Number of latest orders to return.
     * @return array<int, array<string, mixed>> Order payload batch.
     */
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
