<?php
namespace StudioNet\GraphQL\Support\Definition;

use GraphQL\Type\Definition\ObjectType;

/**
 * Define some useful methods to perform a Type creation without creating many
 * and many classes
 *
 * @see DefinitionInterface
 * @abstract
 */
abstract class Definition implements DefinitionInterface {
	/** @var array $cache */
	protected $cache = [];

	/** @var List of transformers to apply when needed $transformers */
	public $transformers = [
		'list'  => \StudioNet\GraphQL\Support\Transformer\Eloquent\ListTransformer::class,
		'view'  => \StudioNet\GraphQL\Support\Transformer\Eloquent\ViewTransformer::class,
		'drop'  => \StudioNet\GraphQL\Support\Transformer\Eloquent\DropTransformer::class,
		'store' => \StudioNet\GraphQL\Support\Transformer\Eloquent\StoreTransformer::class,
		'batch' => \StudioNet\GraphQL\Support\Transformer\Eloquent\BatchTransformer::class
	];

	/**
	 * Return fetchable fields
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [];
	}

	/**
	 * Return mutable fields
	 *
	 * @return array
	 */
	public function getMutable() {
		return [];
	}

	/**
	 * Return wanted transformers
	 *
	 * @return array
	 */
	public function getTransformers() {
		return [
			'list'  => true,
			'view'  => true,
			'drop'  => true,
			'store' => true,
			'batch' => true
		];
	}

	/**
	 * Resolve fetchable type
	 *
	 * @return array
	 */
	public function resolveType() {
		if (!array_key_exists('resolveFetchableType', $this->cache)) {
			$this->setCache('resolveFetchableType', new ObjectType([
				'name'        => $this->getName(),
				'description' => $this->getDescription(),
				'fields'      => [$this, 'getFetchable']
			]));
		}

		return $this->getCache('resolveFetchableType');
	}

	/**
	 * Return cache element
	 *
	 * @param  string $name
	 * @return mixed
	 */
	public function getCache($name) {
		return (array_key_exists($name, $this->cache)) ? $this->cache[$name] : null;
	}

	/**
	 * Set cache element
	 *
	 * @param  string $name
	 * @param  mixed $data
	 * @return void
	 */
	public function setCache($name, $data) {
		$this->cache[$name] = $data;
	}
}
