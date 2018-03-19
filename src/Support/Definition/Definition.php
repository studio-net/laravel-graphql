<?php
namespace StudioNet\GraphQL\Support\Definition;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;
use StudioNet\GraphQL\Definition\Type;

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

	/** @var array $transformers List of transformers to apply when needed */
	public $transformers = [
		'list' => false,
		'view' => false,
		'drop' => false,
		'store' => false,
		'batch' => false,
		'restore' => false
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
	 * Return filterable fields
	 *
	 * @return array
	 */
	public function getFilterable() {
		return [];
	}

	/**
	 * Return mutation fields rules
	 *
	 * @return array
	 */
	public function getRules() {
		return [];
	}

	/**
	 * Return wanted transformers
	 *
	 * @return array
	 */
	public function getTransformers() {
		return [
			'list' => false,
			'view' => false,
			'drop' => false,
			'store' => false,
			'batch' => false,
			'restore' => false
		];
	}

	/**
	 * Resolve fetchable type
	 *
	 * @return ObjectType
	 */
	public function resolveType() {
		if (!array_key_exists('resolveFetchableType', $this->cache)) {
			$this->setCache('resolveFetchableType', new ObjectType([
				'name' => $this->getName(),
				'description' => $this->getDescription(),
				'fields' => function () {
					return $this->resolveFields();
				}
			]));
		}

		return $this->getCache('resolveFetchableType');
	}

	/**
	 * Return resolve type
	 *
	 * @return array
	 */
	public function resolveInputType() {
		if (!array_key_exists('resolveInputType', $this->cache)) {
			$this->setCache('resolveInputType', new InputObjectType([
				'name' => sprintf('%sInput', ucfirst($this->getName())),
				'fields' => function () {
					return array_merge(['id' => Type::id()], $this->getMutable());
				}
			]));
		}

		return $this->getCache('resolveInputType');
	}

	/**
	 * Resolve fields
	 *
	 * @return array
	 */
	protected function resolveFields() {
		$fields = [];

		foreach ($this->getFetchable() as $key => $data) {
			$resolved = false;
			$name = $key;

			if (is_array($data) and array_key_exists('name', $data)) {
				$name = $data['name'];
				$resolved = array_key_exists('resolve', $data);
			} elseif (!is_array($data)) {
				$data = ['type' => $data];
				$resolved = false;
			}

			$method = sprintf('resolve%sField', ucfirst(camel_case($name)));
			$fields[$key] = $data;

			if (!$resolved and method_exists($this, $method)) {
				$fields[$key] = array_merge($fields[$key], [
					'resolve' => [$this, $method]
				]);
			}
		}

		return $fields;
	}

	/**
	 * Assert rights
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @param string $queryType (list|store|drop)
	 * @param array $data
	 * @throws Exception
	 */
	public function assertAcl($queryType, $data) {
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
