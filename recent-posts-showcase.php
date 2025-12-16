<?php
/**
 * Plugin Name:       Recent Posts Showcase
 * Description:       A dynamic block to showcase recent posts with different layouts and styles.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.2
 * Author:            bayzidmiah
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       recent-posts-showcase
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Constants for paths.
 */
define( 'RPS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'src/recent-posts-showcase/' );

/**
 * Registers the block using a `blocks-manifest.php` file, which improves the performance of block type registration.
 * Behind the scenes, it also registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function create_block_recent_posts_showcase_block_init() {
	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
	 * based on the registered block metadata.
	 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
	 *
	 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 */
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
		wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		return;
	}

	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` file.
	 * Added to WordPress 6.7 to improve the performance of block type registration.
	 *
	 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
	 */
	if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
		wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	}
	/**
	 * Registers the block type(s) in the `blocks-manifest.php` file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
	foreach ( array_keys( $manifest_data ) as $block_type ) {
		register_block_type( __DIR__ . "/build/{$block_type}" );
	}
}
add_action( 'init', 'create_block_recent_posts_showcase_block_init' );

/**
 * Enqueue swiper assets.
 */
function rps_enqueue_swiper_assets() {
	// Only enqueue on frontend if needed.
	wp_enqueue_style( 'swiper-css', plugins_url( 'node_modules/swiper/swiper-bundle.min.css', __FILE__ ) );
	wp_enqueue_script( 'swiper-js', plugins_url( 'node_modules/swiper/swiper-bundle.min.js', __FILE__ ), array(), false, true );

	// Enqueue custom JS for Swiper initialization on the frontend.
	wp_enqueue_script(
		'rps-swiper-init',
		RPS_PLUGIN_URL . 'assets/swiper-init.js',
		array( 'swiper-js' ),
		false,
		true
	);

	// Enqueue custom JS for Load More functionality on the frontend.
	wp_enqueue_script(
		'rps-load-more',
		RPS_PLUGIN_URL . 'assets/load-more.js',
		array(),
		false,
		true
	);

	// Localize data for the load-more script.
	wp_localize_script(
		'rps-load-more',
		'recentPostsShowcaseLoadMore',
		array(
			'restUrl' => esc_url_raw( rest_url( 'recent-posts-showcase/v1/load-more' ) ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rps_enqueue_swiper_assets' );

// Hook into REST API initialization.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'recent-posts-showcase/v1',
			'/load-more',
			array(
				'methods'             => 'GET',
				'callback'            => 'recent_posts_showcase_load_more',
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_type'      => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'           => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'posts_per_page' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'taxonomy'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'terms'          => array(
						'required'          => false,
						'sanitize_callback' => function ( $value ) {
							return array_map( 'intval', (array) $value );
						},
					),
					'displayImage'   => array(
						'required'          => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'displayExcerpt' => array(
						'required'          => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'displayAuthor'  => array(
						'required'          => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'displayDate'    => array(
						'required'          => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'layout'         => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
);

/**
 * Callback for Load More.
 *
 * @param rest_request $request The REST request object.
 * @return array The response containing HTML and hasMore flag.
 */
function recent_posts_showcase_load_more( $request ) {
	$post_type      = sanitize_text_field( $request['post_type'] );
	$page           = max( 1, absint( $request['page'] ) );
	$posts_per_page = max( 1, absint( $request['posts_per_page'] ) );
	$taxonomy       = ! empty( $request['taxonomy'] ) ? sanitize_text_field( $request['taxonomy'] ) : '';

	$terms = array();
	if ( ! empty( $request['terms'] ) ) {
		if ( is_string( $request['terms'] ) ) {
			$terms = array_map( 'intval', explode( ',', $request['terms'] ) );
		} elseif ( is_array( $request['terms'] ) ) {
			$terms = array_map( 'intval', $request['terms'] );
		}
	}

	$display_image   = rest_sanitize_boolean( $request['displayImage'] ?? false );
	$display_excerpt = rest_sanitize_boolean( $request['displayExcerpt'] ?? false );
	$display_author  = rest_sanitize_boolean( $request['displayAuthor'] ?? false );
	$display_date    = rest_sanitize_boolean( $request['displayDate'] ?? false );
	$layout          = sanitize_text_field( $request['layout'] ?? 'grid' );

	$args = array(
		'post_type'           => $post_type,
		'posts_per_page'      => $posts_per_page,
		'paged'               => $page,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	);

	if ( $taxonomy && is_array( $terms ) && count( $terms ) > 0 ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $terms,
			),
		);
	}

	$query = new WP_Query( $args );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			echo '<div class="recent-post-item">';
			if ( $display_image && has_post_thumbnail() ) {
				echo '<div class="recent-post-thumbnail">';
				the_post_thumbnail( 'medium' );
				echo '</div>';
			}
			echo '<div class="rps-content-wrapper">';
			echo '<h3 class="recent-post-title"><a href="' . esc_url( get_permalink() ) . '">' . get_the_title() . '</a></h3>';

			if ( $display_author || $display_date ) {
				echo '<div class="recent-post-meta">';
				if ( $display_author ) {
					echo '<span class="post-author">' . esc_html( get_the_author() ) . '</span>';
				}
				if ( $display_date ) {
					echo ' <span class="post-date">' . esc_html( get_the_date() ) . '</span>';
				}
				echo '</div>';
			}

			if ( $display_excerpt ) {
				echo '<div class="recent-post-excerpt">' . wp_kses_post( get_the_excerpt() ) . '</div>';
			}
			echo '</div>'; // .rps-content-wrapper
			echo '</div>'; // .recent-post-item
		}
		wp_reset_postdata();
	}

	return array(
		'html'    => ob_get_clean(),
		'hasMore' => ( $page < $query->max_num_pages ),
	);
}
