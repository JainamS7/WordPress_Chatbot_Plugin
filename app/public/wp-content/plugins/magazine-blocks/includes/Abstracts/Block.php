<?php
/**
 * Block abstract block.
 *
 * @since x.x.x
 *
 * @package Magazine Blocks
 */
namespace MagazineBlocks\Abstracts;

use MagazineBlocks\Interfaces\RenderableInterface;
use MagazineBlocks\Services\BlockRegistrar;
use MagazineBlocks\Services\PostQueryBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class.
 *
 * @abstract
 * @since x.x.x
 * @package Magazine Blocks
 */
abstract class Block implements RenderableInterface {

	/**
	 * The unique name.
	 *
	 * @var string
	 * @since x.x.x
	 */
	protected $block_name;

	/**
	 * Query builder service for handling post queries.
	 *
	 * @var PostQueryBuilder
	 * @since x.x.x
	 */
	protected $query_builder;

	/**
	 * Block registrar service for registering blocks.
	 *
	 * @var BlockRegistrar
	 * @since x.x.x
	 */
	protected $registrar;

	/**
	 * Constructor - Initialize the block with required services.
	 *
	 * @param BlockRegistrar   $registrar     The block registration service.
	 * @param PostQueryBuilder $query_builder The post query building service.
	 *
	 * @since x.x.x
	 */
	public function __construct(
		BlockRegistrar $registrar,
		PostQueryBuilder $query_builder
	) {
		$this->registrar     = $registrar;
		$this->query_builder = $query_builder;
		$this->register();
	}

	/**
	 * Register the block.
	 *
	 * @since x.x.x
	 */
	protected function register() {
		$this->registrar->register(
			$this->block_name,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the block output.
	 *
	 * @param array       $attributes Block attributes from the editor.
	 * @param string      $content    Block inner content (for dynamic blocks).
	 * @param object|null $block   The block instance.
	 *
	 * @return string The rendered HTML output for the block.
	 * @since x.x.x
	 */
	abstract public function render(
		$attributes = array(),
		$content = '',
		$block = null
	);
}
