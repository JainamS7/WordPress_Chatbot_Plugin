<?php
/**
 * Plugin Name: Magazine Blocks
 * Description: Craft your beautifully unique and dynamic Magazine, Newspaper website with various beautiful and advanced posts related blocks like Featured Posts, Banner Posts, Grid Module, Tab Posts, and more.
 * Author: WPBlockArt
 * Author URI: https://wpblockart.com/
 * Version: 1.8.2
 * Requires at least: 5.4
 * Requires PHP: 7.0
 * Text Domain: magazine-blocks
 * Domain Path: /languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Magazine Blocks
 */

use MagazineBlocks\Blocks;
use MagazineBlocks\Helpers\PostHelper;
use MagazineBlocks\MagazineBlocks;

defined( 'ABSPATH' ) || exit;

! defined( 'MAGAZINE_BLOCKS_VERSION' ) && define( 'MAGAZINE_BLOCKS_VERSION', '1.8.2' );
! defined( 'MAGAZINE_BLOCKS_PLUGIN_FILE' ) && define( 'MAGAZINE_BLOCKS_PLUGIN_FILE', __FILE__ );
! defined( 'MAGAZINE_BLOCKS_PLUGIN_DIR' ) && define( 'MAGAZINE_BLOCKS_PLUGIN_DIR', __DIR__ );
! defined( 'MAGAZINE_BLOCKS_PLUGIN_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
! defined( 'MAGAZINE_BLOCKS_ASSETS' ) && define( 'MAGAZINE_BLOCKS_ASSETS', __DIR__ . '/assets' );
! defined( 'MAGAZINE_BLOCKS_ASSETS_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_ASSETS_DIR_URL', MAGAZINE_BLOCKS_PLUGIN_DIR_URL . 'assets' );
! defined( 'MAGAZINE_BLOCKS_DIST_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_DIST_DIR_URL', MAGAZINE_BLOCKS_PLUGIN_DIR_URL . 'dist' );
! defined( 'MAGAZINE_BLOCKS_LANGUAGES' ) && define( 'MAGAZINE_BLOCKS_LANGUAGES', __DIR__ . '/languages' );
! defined( 'MAGAZINE_BLOCKS_UPLOAD_DIR' ) && define( 'MAGAZINE_BLOCKS_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/magazine-blocks' );
! defined( 'MAGAZINE_BLOCKS_UPLOAD_DIR_URL' ) && define( 'MAGAZINE_BLOCKS_UPLOAD_DIR_URL', wp_upload_dir()['baseurl'] . '/magazine-blocks' );

// Load the autoloader.
require_once __DIR__ . '/vendor/autoload.php';

if ( ! function_exists( 'magazine_blocks' ) ) {
	/**
	 * Returns the main instance of Magazine Blocks to prevent the need to use globals.
	 *
	 * @return MagazineBlocks
	 */
	function magazine_blocks() {
		return MagazineBlocks::init();
	}
}

magazine_blocks();

/**
 * Create API fields for additional info
 *
 * @since 1.0.9
 */
function magazine_blocks_register_rest_fields() {
	$post_type = PostHelper::get_post_types();

	foreach ( $post_type as $key => $value ) {
		// Featured image.
		register_rest_field(
			$value['value'],
			'magazine_blocks_featured_image_url',
			array(
				'get_callback'    => 'magazine_blocks_get_featured_image_url',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Author info.
		register_rest_field(
			$value['value'],
			'magazine_blocks_author',
			array(
				'get_callback'    => 'magazine_blocks_get_author_info',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Add comment info.
		register_rest_field(
			$value['value'],
			'magazine_blocks_comment',
			array(
				'get_callback'    => 'magazine_blocks_get_comment_info',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Add comment info.
		register_rest_field(
			$value['value'],
			'magazine_blocks_author_image',
			array(
				'get_callback'    => 'magazine_blocks_get_author_image',
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Category links.
		register_rest_field(
			$value['value'],
			'magazine_blocks_category',
			array(
				'get_callback'    => 'magazine_blocks_get_category_list',
				'update_callback' => null,
				'schema'          => array(
					'description' => esc_html__( 'Category list links' ),
					'type'        => 'string',
				),
			)
		);
	}
}

// Feature image.
function magazine_blocks_get_featured_image_url( $object ) {

	$featured_images = array();
	if ( ! isset( $object['featured_media'] ) ) {
		return $featured_images;
	} else {

		$full                         = wp_get_attachment_image_src( $object['featured_media'], 'full', false );
		$medium                       = wp_get_attachment_image_src( $object['featured_media'], 'medium', false );
		$thumbnail                    = wp_get_attachment_image_src( $object['featured_media'], 'thumbnail', false );
		$featured_images['full']      = $full;
		$featured_images['medium']    = $medium;
		$featured_images['thumbnail'] = $thumbnail;
		return $featured_images;
	}
}

// Author.
function magazine_blocks_get_author_info( $object ) {
	$author = ( isset( $object['author'] ) ) ? $object['author'] : '';

	$author_data['display_name'] = get_the_author_meta( 'display_name', $author );
	$author_data['author_link']  = get_author_posts_url( $author );

	return $author_data;
}

// Comment.
function magazine_blocks_get_comment_info( $object ) {
	$comments_count = wp_count_comments( $object['id'] );
	return $comments_count->total_comments;
}

// Author Image.
function magazine_blocks_get_author_image( $object ) {
	$author = ( isset( $object['author'] ) ) ? $object['author'] : '';

	$author_image = get_avatar_url( $author );

	return $author_image;
}

// Category list.
if ( ! function_exists( 'magazine_blocks_get_category_list' ) ) {
	function magazine_blocks_get_category_list( $object ) {
		$taxonomies = get_post_taxonomies( $object['id'] );
		$post_id    = $object['id'];
		$output     = '';

		if ( 'post' === get_post_type( $post_id ) ) {
			$categories = get_the_category( $post_id );
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					$class   = 'category-link category-link-' . esc_attr( $category->term_id );
					$output .= '<a href="#" class="' . $class . '">' . esc_html( $category->name ) . '</a> ';}
			}
		} elseif ( ! empty( $taxonomies ) ) {
			$terms = get_the_terms( $post_id, $taxonomies[0] );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$class   = 'category-link category-link-' . esc_attr( $term->term_id );
					$output .= '<a href="#" class="' . $class . '">' . esc_html( $term->name ) . '</a> ';
				}
			}
		}
		return trim( $output );
	}
}
add_action( 'rest_api_init', 'magazine_blocks_register_rest_fields' );

add_action(
	'enqueue_block_editor_assets',
	function () {
		$categories = get_categories();
		echo '<style id="magazine-blocks-category-colors">';
		$enable_override_category_color = get_theme_mod( 'colormag_enable_override_category_color', false );
		if ( function_exists( 'colormag_category_color' ) && $enable_override_category_color ) {
			foreach ( $categories as $category ) {
				$color = colormag_category_color( $category->term_id );
				if ( $color ) {
					echo '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: ' . esc_attr( $color ) . ';}';
				}
			}
		} else {
			foreach ( $categories as $category ) {
				echo '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: var(--mzb-categories-colors-' . esc_attr( $category->term_id ) . ');}';
			}
		}
		echo '</style>';
	}
);

add_action(
	'wp_head',
	function () {
		$categories = get_categories();
		echo '<style id="magazine-blocks-category-colors">';
		$enable_override_category_color = get_theme_mod( 'colormag_enable_override_category_color', false );
		if ( function_exists( 'colormag_category_color' ) && $enable_override_category_color ) {
			foreach ( $categories as $category ) {
				$color = colormag_category_color( $category->term_id );
				if ( $color ) {
					echo '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: ' . esc_attr( $color ) . ';}';
				}
			}
		} else {
			foreach ( $categories as $category ) {
				$settings = magazine_blocks_get_setting( 'global-styles' );
				$settings = json_decode( $settings, true );
				$color    = $settings['categories_color'] ?? array();
				if ( ! empty( $color ) ) {
					foreach ( $color as $setting => $value ) {
						if ( isset( $value['id'], $value['value'] ) && $value['id'] == $category->term_id ) {
							echo '.mzb-post-meta .mzb-post-categories .category-link-' . esc_attr( $category->term_id ) . '{background-color: ' . esc_attr( $value['value'] ) . ';}';
						}
					}
				}
			}
		}
		echo '</style>';
	}
);

add_action( 'wp_ajax_magazine_blocks_pagination_load', 'magazine_blocks_pagination_load' );
add_action( 'wp_ajax_nopriv_magazine_blocks_pagination_load', 'magazine_blocks_pagination_load' );

function magazine_blocks_pagination_load() {
	$page = intval( $_GET['page'] );

	$att = $_POST['att']; // Pass the attributes needed for rendering

	// Modify the attributes with the new page number
	$att['paged'] = $page;

	// Render the posts using your existing function
	$html = Blocks::render_block_magazine_blocks_featured_posts( $att );

	// Return the HTML as the response
	wp_send_json_success( array( 'html' => $html ) );

	wp_die();
}
