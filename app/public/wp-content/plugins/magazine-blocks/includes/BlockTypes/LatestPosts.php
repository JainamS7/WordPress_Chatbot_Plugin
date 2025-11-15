<?php
/**
 * LatestPosts block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;
use MagazineBlocks\Traits\Blocks\PostRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * LatestPosts block class.
 */
class LatestPosts extends Block {

	use PostRenderer;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'latest-posts';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {
		$attrs = $this->extract_attributes( $attributes );

		$categories = get_categories();
		$posts      = $this->get_latest_posts_by_category(
			$categories,
			$attrs['excluded_category'],
			$attrs['offset'],
			$attrs['post_type']
		);

		$query = $this->query_builder->build_query( array( 'paged' => $attrs['paged'] ) );

		$attrs['max_num_pages'] = $query->max_num_pages;

		return $this->render_block( $posts, $attrs );
	}

	/**
	 * Extract attributes.
	 *
	 * @param array $attributes Original block attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$layout         = magazine_blocks_array_get( $attributes, 'layout', 'layout-1' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', '' );

		// Get the specific advanced styles based on layout and heading layout.
		$advanced_style = magazine_blocks_array_get( $attributes, magazine_get_style_key( $layout ), '' );
		$heading_style  = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );
		return array(
			'client_id'         => $client_id,
			'class_name'        => magazine_blocks_array_get( $attributes, 'className', '' ),
			'label'             => magazine_blocks_array_get( $attributes, 'label', '' ),
			'layout'            => $layout,
			'advanced_style'    => $advanced_style,
			'heading_layout'    => $heading_layout,
			'heading_style'     => $heading_style,
			'column'            => magazine_blocks_array_get( $attributes, 'column', 2 ),
			'post_type'         => magazine_blocks_array_get( $attributes, 'postType', 'post' ),
			'excluded_category' => magazine_blocks_array_get( $attributes, 'excludedCategory', array() ),
			'offset'            => magazine_blocks_array_get( $attributes, 'offset', 0 ),
			// Display toggles.
			'enable_heading'    => magazine_blocks_array_get( $attributes, 'enableHeading', true ),
			'enable_post_title' => magazine_blocks_array_get( $attributes, 'enablePostTitle', true ),
			'enable_excerpt'    => magazine_blocks_array_get( $attributes, 'enableExcerpt', false ),
			'enable_read_more'  => magazine_blocks_array_get( $attributes, 'enableReadMore', false ),
			'read_more_text'    => magazine_blocks_array_get( $attributes, 'readMoreText', '' ),
			'excerpt_limit'     => magazine_blocks_array_get( $attributes, 'excerptLimit', '' ),
			'enable_author'     => magazine_blocks_array_get( $attributes, 'enableAuthor', false ),
			'enable_date'       => magazine_blocks_array_get( $attributes, 'enableDate', false ),
			'enable_icon'       => magazine_blocks_array_get( $attributes, 'enableIcon', false ),
			'meta_position'     => magazine_blocks_array_get( $attributes, 'metaPosition', 'bottom' ),
			'hover_animation'   => magazine_blocks_array_get( $attributes, 'hoverAnimation', '' ),
			'hide_on_desktop'   => magazine_blocks_array_get( $attributes, 'hideOnDesktop', false ),
			// Style options.
			'layout1_style'     => magazine_blocks_array_get( $attributes, 'layout1AdvancedStyle', '' ),
			'layout2_style'     => magazine_blocks_array_get( $attributes, 'layout2AdvancedStyle', '' ),
			'enable_pagination' => magazine_blocks_array_get( $attributes, 'enablePagination', false ),
			// pagination.
			'paged'             => isset( $_GET[ 'block_id_' . $client_id ], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'mzb_latest_posts' ) ? max( 1, intval( $_GET[ 'block_id_' . $client_id ] ) ) : 1,

		);
	}

	/**
	 * Get latest posts grouped by category.
	 *
	 * @param mixed $categories The categories list.
	 * @param mixed $excluded_category The excluded category list.
	 * @param mixed $offset The offset.
	 * @param mixed $post_type  Th post type.
	 */
	protected function get_latest_posts_by_category( $categories, $excluded_category, $offset, $post_type ) {
		if ( ! is_array( $excluded_category ) ) {
			$excluded_category = empty( $excluded_category ) ? array() : array( $excluded_category );
		}

		$latest_posts    = array();
		$displayed_posts = array();

		foreach ( $categories as $category ) {
			if ( ! in_array( $category->term_id, $excluded_category, true ) ) {
				$post = $this->get_latest_post_in_category( $category->term_id, $excluded_category, $offset, $post_type );

				if ( $post && ! in_array( $post->ID, $displayed_posts, true ) ) {
					$displayed_posts[] = $post->ID;
					$latest_posts[]    = $post;
				}
			}
		}

		return $latest_posts;
	}

	/**
	 * Get single latest post in a category.
	 *
	 * @param mixed $category_id The categories ID.
	 * @param mixed $excluded_category The excluded category list.
	 * @param mixed $offset The offset.
	 * @param mixed $post_type  Th post type.
	 */
	protected function get_latest_post_in_category( $category_id, $excluded_category, $offset, $post_type ) {
		$latest_posts = get_posts(
			array(
				'post_type'        => $post_type,
				'category'         => $category_id,
				'numberposts'      => 1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'category__not_in' => $excluded_category,
				'offset'           => $offset,
			)
		);

		return ! empty( $latest_posts ) ? $latest_posts[0] : null;
	}

	/**
	 * Render block wrapper.
	 *
	 * @param mixed $posts The posts.
	 * @param mixed $attrs The attributes.
	 */
	protected function render_block( $posts, $attrs ) {
		$block_class = "mzb-latest-posts mzb-latest-posts-{$attrs['client_id']} {$attrs['class_name']}";
		if ( $attrs['hide_on_desktop'] ) {
			$block_class .= ' magazine-blocks-hide-on-desktop';
		}

		$posts_class = "mzb-posts mzb-{$attrs['layout']} mzb-post-col--" . ( $attrs['column'] ? $attrs['column'] : 4 );
		if ( 'layout-1' === $attrs['layout'] ) {
			$posts_class .= " mzb-{$attrs['layout1_style']}";
		} elseif ( 'layout-2' === $attrs['layout'] ) {
			$posts_class .= " mzb-{$attrs['layout2_style']}";
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $block_class ); ?>">
			<?php if ( $attrs['enable_heading'] ) : ?>
				<div class="mzb-post-heading mzb-<?php echo esc_attr( $attrs['heading_layout'] ); ?> mzb-<?php echo esc_attr( $attrs['heading_style'] ); ?>">
					<h2 class="mzb-heading-text"><?php echo esc_html( $attrs['label'] ); ?></h2>
				</div>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $posts_class ); ?>">
				<?php foreach ( $posts as $post ) : ?>
					<div class="mzb-post">
						<?php echo $this->render_featured_image( $post->ID, $attrs['hover_animation'] ); ?>

						<div class="mzb-post-content">
							<?php if ( 'top' === $attrs['meta_position'] ) : ?>
								<?php echo $this->render_meta_section( $post->ID, $attrs ); ?>
							<?php endif; ?>

							<?php if ( $attrs['enable_post_title'] ) : ?>
								<?php echo $this->render_post_title( $post->ID, 'h3' ); ?>
							<?php endif; ?>

							<?php if ( 'bottom' === $attrs['meta_position'] ) : ?>
								<?php echo $this->render_meta_section( $post->ID, $attrs ); ?>
							<?php endif; ?>

							<?php if ( $attrs['enable_excerpt'] || $attrs['enable_read_more'] ) : ?>
								<?php
								echo $this->render_excerpt_and_read_more(
									$post->ID,
									array(
										'enable_excerpt'  => $attrs['enable_excerpt'],
										'excerpt_limit'   => $attrs['excerpt_limit'],
										'enable_readmore' => $attrs['enable_read_more'],
										'read_more_text'  => $attrs['read_more_text'],
									)
								);
								?>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>

				<?php if ( $attrs['enable_pagination'] ) : ?>
					<div class="mzb-pagination-numbers">
						<h2><?php echo esc_html( mzb_numbered_pagination( $attrs['max_num_pages'], $attrs['paged'], $attrs['client_id'] ) ); ?></h2>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render meta section (author/date/icons).
	 *
	 * @param mixed $post_id The post id.
	 * @param mixed $attrs The attributes.
	 */
	protected function render_meta_section( $post_id, $attrs ) {
		$meta_items = array();

		if ( $attrs['enable_author'] ) {
			$meta_items[] = $this->render_author( $post_id, $attrs['enable_icon'] );
		}

		if ( $attrs['enable_date'] ) {
			$meta_items[] = $this->render_date( $post_id, $attrs['enable_icon'] );
		}

		if ( empty( $meta_items ) ) {
			return '';
		}

		return sprintf( '<div class="mzb-post-entry-meta">%s</div>', implode( '', $meta_items ) );
	}
}
