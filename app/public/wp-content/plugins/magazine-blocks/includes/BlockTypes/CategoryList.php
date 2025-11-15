<?php
/**
 * Category List block.
 *
 * @package Magazine Blocks
 */

namespace MagazineBlocks\BlockTypes;

use MagazineBlocks\Abstracts\Block;

defined( 'ABSPATH' ) || exit;

/**
 * Category List block class.
 */
class CategoryList extends Block {


	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'category-list';

	/**
	 * Render the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @param object $block      Block object.
	 * @return string Rendered HTML output.
	 */
	public function render( $attributes = array(), $content = '', $block = null ) {

		$attrs      = $this->extract_attributes( $attributes );
		$categories = $this->get_categories( $attrs );

		return $this->render_block( $categories, $attrs );
	}

	/**
	 * Extract and process block attributes.
	 *
	 * @param array $attributes Raw block attributes.
	 * @return array Processed attributes.
	 */
	protected function extract_attributes( $attributes ) {
		$client_id      = magazine_blocks_array_get( $attributes, 'clientId', '' );
		$layout         = magazine_blocks_array_get( $attributes, 'layout', '' );
		$heading_layout = magazine_blocks_array_get( $attributes, 'headingLayout', '' );

		// Get the specific advanced styles based on layout and heading layout.
		$advanced_style = magazine_blocks_array_get( $attributes, magazine_get_style_key( $layout ), '' );
		$heading_style  = magazine_blocks_array_get( $attributes, magazine_get_heading_style_key( $heading_layout ), '' );

		// Icon settings.
		$icon_list   = magazine_blocks_array_get( $attributes, 'categoryArrow', array() );
		$icon        = isset( $icon_list['icon'] ) ? magazine_blocks_get_icon( $icon_list['icon'], false ) : '';
		$enable_icon = isset( $icon_list['enable'] ) ? $icon_list['enable'] : false;

		return array(
			// General attributes.
			'client_id'      => $client_id,
			'layout'         => $layout,
			'advanced_style' => $advanced_style,
			'post_box_style' => magazine_blocks_array_get( $attributes, 'postBoxStyle', 'true' ),
			'category_count' => magazine_blocks_array_get( $attributes, 'categoryCount', '4' ),

			// Icon settings.
			'icon'           => $icon,
			'enable_icon'    => $enable_icon,

			// Heading settings.
			'enable_heading' => magazine_blocks_array_get( $attributes, 'enableHeading', '' ),
			'heading_layout' => $heading_layout,
			'heading_style'  => $heading_style,
			'label'          => magazine_blocks_array_get( $attributes, 'label', 'Categories' ),
		);
	}

	/**
	 * Get categories based on attributes.
	 *
	 * @param array $attrs Processed attributes.
	 * @return array Categories.
	 */
	protected function get_categories( $attrs ) {
		return get_categories(
			array(
				'hide_empty'          => 1,
				'number'              => $attrs['category_count'],
				'ignore_sticky_posts' => 1,
			)
		);
	}

	/**
	 * Render the main block HTML structure.
	 *
	 * @param array $categories Categories array.
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	protected function render_block( $categories, $attributes ) {
		$html = sprintf(
			'<div class="mzb-category-list mzb-category-list-%s">',
			esc_attr( $attributes['client_id'] )
		);

		// Render heading.
		$html .= $this->render_heading( $attributes );

		// Render categories.
		$html .= $this->render_categories_container( $categories, $attributes );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render heading section.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Heading HTML.
	 */
	protected function render_heading( $attributes ) {
		if ( ! $attributes['enable_heading'] ) {
			return '';
		}

		return sprintf(
			'<div class="mzb-post-heading mzb-%s mzb-%s"><h2 class="mzb-heading-text">%s</h2></div>',
			esc_attr( $attributes['heading_layout'] ),
			esc_attr( $attributes['heading_style'] ),
			esc_html( $attributes['label'] )
		);
	}

	/**
	 * Render categories container.
	 *
	 * @param array $categories Categories array.
	 * @param array $attributes Block attributes.
	 * @return string Categories HTML.
	 */
	protected function render_categories_container( $categories, $attributes ) {
		$classes = array(
			'mzb-posts',
			'mzb-' . $attributes['layout'],
			'mzb-' . $attributes['advanced_style'],
		);

		// Add separator class for layout-3.
		if ( 'layout-3' === $attributes['layout'] && $attributes['post_box_style'] ) {
			$classes[] = 'separator';
		}

		$html = sprintf( '<div class="%s">', implode( ' ', array_filter( $classes ) ) );

		foreach ( $categories as $category ) {
			$html .= $this->render_category( $category, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render individual category.
	 *
	 * @param \WP_Term $category Category object.
	 * @param array    $attributes Block attributes.
	 * @return string Category HTML.
	 */
	protected function render_category( $category, $attributes ) {
		$cat_id         = $category->term_id;
		$advanced_style = $attributes['advanced_style'];

		$html = sprintf( '<div class="mzb-post mzb-%s">', $cat_id );

		// Handle different layout styles.
		if ( 'layout-1-style-2' === $advanced_style ) {
			$html .= $this->render_layout_1_style_2( $category, $cat_id, $attributes );
		} elseif ( 'layout-2-style-2' === $advanced_style ) {
			$html .= $this->render_layout_2_style_2( $category, $cat_id, $attributes );
		} elseif ( 'layout-2-style-3' === $advanced_style ) {
			$html .= $this->render_layout_2_style_3( $category, $cat_id, $attributes );
		} else {
			// Default layout (includes layout-1-style-3 functionality).
			$html .= $this->render_default_layout( $category, $cat_id, $attributes );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render layout 1 style 2.
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_layout_1_style_2( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$color_style = '';
		if ( function_exists( 'colormag_category_color' ) ) {
			$color_style = 'style="background-color:' . colormag_category_color( $cat_id ) . ';"';
		}

		return '
			<div class="mzb-title-wrapper" ' . $background_style . '>
				<div class="mzb-title" ' . $color_style . '>
					<span class="mzb-post-categories">
						<a href="' . get_category_link( $cat_id ) . '">' . esc_html( $category->name ) . '</a>
					</span>
					<div class="mzb-post-count-wrapper">
						<div class="mzb-post-count">
							<a href="' . get_category_link( $cat_id ) . '">' . $category->category_count . ' Posts</a>
						</div>
					</div>
				</div>
			</div>
		';
	}

	/**
 * Render layout 2 style 2.
 *
 * @param \WP_Term $category Category object.
 * @param int      $cat_id Category ID.
 * @param array    $attributes Block attributes.
 * @return string Layout HTML.
 */
	protected function render_layout_2_style_2( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$color_style = '';
		if ( function_exists( 'colormag_category_color' ) ) {
			$color_style = 'style="background-color:' . colormag_category_color( $cat_id ) . ';"';
		}

		$html  = '<div class="mzb-title-wrapper" ' . $background_style . '>';
		$html .= '<div class="mzb-title">';
		$html .= '<span class="mzb-post-categories">';
		$html .= '<a href="' . get_category_link( $cat_id ) . '">' . esc_html( $category->name ) . '</a>';
		$html .= '</span>';
		$html .= '<div class="mzb-post-count-wrapper">';
		$html .= '<div class="mzb-post-count">';
		$html .= '<a href="' . get_category_link( $cat_id ) . '">' . $category->category_count . ' Posts</a>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="mzb-overlay"></div>';
		if ( $attributes['enable_icon'] ) {
			$html .= '<div class="mzb-list-icon">';
			$html .= '<a href="' . get_category_link( $cat_id ) . '">' . $attributes['icon'] . '</a>';
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render layout 2 style 3.
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_layout_2_style_3( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$html  = '<div class="mzb-title-wrapper" ' . $background_style . '>';
		$html .= '<div class="mzb-overlay"></div>';
		$html .= '</div>';

		$html .= '<div class="mzb-title-with-icon">';
		$html .= '<div class="mzb-title">';
		$html .= '<span class="mzb-post-categories">';
		$html .= '<a href="' . get_category_link( $cat_id ) . '">' . esc_html( $category->name ) . '</a>';
		$html .= '</span>';
		$html .= '<div class="mzb-post-count-wrapper">';
		$html .= '<div class="mzb-post-count">';
		$html .= '<a href="' . get_category_link( $cat_id ) . '">' . $category->category_count . ' Posts</a>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		if ( $attributes['enable_icon'] ) {
			$html .= '<div class="mzb-list-icon">';
			$html .= '<a href="' . get_category_link( $cat_id ) . '">' . $attributes['icon'] . '</a>';
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render default layout (includes layout-1-style-3 functionality).
	 *
	 * @param \WP_Term $category Category object.
	 * @param int      $cat_id Category ID.
	 * @param array    $attributes Block attributes.
	 * @return string Layout HTML.
	 */
	protected function render_default_layout( $category, $cat_id, $attributes ) {
		$src              = $this->get_category_image( $category->slug );
		$background_style = $src ? 'style="background-image: url(' . esc_url( $src ) . ');"' : '';

		$color_style = '';
		if ( function_exists( 'colormag_category_color' ) ) {
			$color_style = 'style="background-color:' . colormag_category_color( $cat_id ) . ';"';
		}

		$html  = '<div class="mzb-title-wrapper" ' . $background_style . '>';
		$html .= '<span class="mzb-post-categories">';
		// $html .= $attributes['enable_icon'] ? '<span class="mzb-list-icon">' . $attributes['icon'] . '</span>' : '';
		$html .= '<a href="' . get_category_link( $cat_id ) . '" ' . $color_style . '>' . esc_html( $category->name ) . '</a>';
		$html .= '</span>';
		$html .= '</div>';
		$html .= '<div class="mzb-post-count-wrapper">';
		$html .= '<div class="mzb-post-count">';
		$html .= '<a href="' . get_category_link( $cat_id ) . '">' . $category->category_count . ' <span class="mzb-post-count-text">Posts</span></a>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get category image.
	 *
	 * @param string $cat_slug Category slug.
	 * @return string Image URL.
	 */
	protected function get_category_image( $cat_slug ) {
		$args = array(
			'category_name'  => $cat_slug,
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'desc',
		);

		$posts = get_posts( $args );

		if ( $posts ) {
			$post_id = $posts[0]->ID;
			if ( has_post_thumbnail( $post_id ) ) {
				return get_the_post_thumbnail_url( $post_id );
			}
		}

		return '';
	}
}
