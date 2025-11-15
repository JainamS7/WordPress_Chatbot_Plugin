<?php

/**
 * SiteBuilder post type.
 */

namespace MagazineBlocks\PostTypes;

class SiteBuilder {


	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mzb-builder-template';

	public function __construct() {
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}

	public function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( $post->post_type !== $this->post_type ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$type = get_post_meta( $post_id, '_mzb_template', true );

		$query = new \WP_Query(
			array(
				'post_type'      => $this->post_type,
				'meta_query'     => array(
					array(
						'key'     => '_mzb_template',
						'value'   => $type,
						'compare' => '=',
					),
				),
				'status'         => 'publish',
				'posts_per_page' => 1,
				'post__not_in'   => array( $post_id ),
			)
		);
		if ( $query->have_posts() ) {
			$active_template = $query->posts[0];
			if ( $active_template->ID === $post_id ) {
				return;
			}
			wp_update_post(
				array(
					'ID'          => $active_template->ID,
					'post_status' => 'draft',
				)
			);
		}
	}

	/**
	 * Get post type.
	 *
	 * @return string
	 */
	protected function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get post type args.
	 *
	 * @return array
	 */
	protected function get_post_type_args() {
		$labels = apply_filters(
			"magazine_blocks_{$this->post_type}_labels",
			array(
				'name'               => __( 'Template', 'blockart' ),
				'singular_name'      => __( 'Template', 'blockart' ),
				'add_new'            => __( 'Add new Template', 'blockart' ),
				'add_new_item'       => __( 'Add new Template', 'blockart' ),
				'edit_item'          => __( 'Edit Template', 'blockart' ),
				'new_item'           => __( 'New Template', 'blockart' ),
				'view_item'          => __( 'View Template', 'blockart' ),
				'search_items'       => __( 'Search Template', 'blockart' ),
				'not_found'          => __( 'No Template found', 'blockart' ),
				'not_found_in_trash' => __( 'No Template found in Trash', 'blockart' ),
				'parent_item_colon'  => '',
			)
		);
		return array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => false,
			'has_archive'        => false,
			'hierarchical'       => false,
			'map_meta_cap'       => true,
			'capability_type'    => 'post',
			'supports'           => array(
				'title',
				'editor',
				'custom-fields',
				'comments',
				'trackbacks',
				'author',
				'page-attributes',
			),
			'show_in_rest'       => true,
			'rest_namespace'     => 'magazine-blocks/v1',
			'rest_base'          => 'builder-templates',
		);
	}

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public function register() {
		$args = apply_filters( "magazine_blocks_{$this->get_post_type()}_post_type_args", $this->get_post_type_args() );
		register_post_type( $this->get_post_type(), $args );

		register_meta(
			'post',
			'_mzb_template',
			array(
				'object_subtype' => $this->get_post_type(),
				'single'         => true,
				'type'           => 'string',
				'auth_callback'  => function () {
					return current_user_can( 'edit_posts' );
				},
				'show_in_rest'   => true,
			)
		);
	}
}
