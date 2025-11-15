<?php
namespace MagazineBlocks\Traits\Blocks;

/**
 * HasHtmlAttributes class trait.
 *
 * @package Magazine Blocks\Traits
 */
trait HasHtmlAttributes {
	/**
	 * Get default HTML attributes.
	 *
	 * @return array
	 */
	protected function get_default_html_attrs() {
		return array(
			'id'    => $this->get_attribute( 'cssID', '', true ),
			'class' => $this->cn(
				"mzb-$this->block_name mzb-$this->block_name-{$this->get_attribute('clientId', '', true)}",
				$this->get_attribute( 'className', '' ),
			),
		);
	}

	/**
	 * Get custom HTML attributes.
	 *
	 * @return array
	 */
	protected function get_html_attrs() {
		return array();
	}

	/**
	 * Build html attributes.
	 *
	 * @param boolean $echo_attrs
	 * @return string
	 */
	protected function build_html_attributes( $echo_attrs = false ) {
		$attrs = wp_parse_args( $this->get_html_attrs(), $this->get_default_html_attrs() );
		return magazine_blocks_build_html_attrs( $attrs, $echo_attrs );
	}
}
