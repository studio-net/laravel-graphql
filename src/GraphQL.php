<?php
namespace StudioNet\GraphQL;

use GraphQL\Executor\Executor;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Cache\CachePool;
use StudioNet\GraphQL\Support\Definition\Definition;

/**
 * GraphQL implementation singleton
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GraphQL {
	/** @var Application $app */
	private $app;

	/** @var CachePool $cache */
	private $cache;

	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @return void
	 */
	public function __construct(Application $app, CachePool $cache) {
		$this->app   = $app;
		$this->cache = $cache;
	}

	/**
	 * Return built schema
	 *
	 * @param  string $name
	 * @return Schema
	 */
	public function getSchema($name) {
		if (!$this->hasSchema($name)) {
			throw new Exception\SchemaNotFoundException('Cannot find schema ' . $name);
		}

		// Represents an array like
		//
		// [
		//    'query'    => [],
		//    'mutation' => []
		// ]
		$schema = $this->get('schema', $name);
		$schema['query']    = $this->manageQuery($schema['query']);
		$schema['mutation'] = $this->manageMutation($schema['mutation']);

		return new Schema($schema);
	}

	/**
	 * Return existing type
	 *
	 * @param  string $name
	 * @return ObjectType
	 */
	public function type($name) {
		$name = strtolower($name);

		if ($this->has('definition', $name)) {
			return $this->get('definition', $name)->resolveType();
		}

		throw new Exception\TypeNotFoundException('Cannot find type ' . $name);
	}

	/**
	 * Return input
	 *
	 * @param  string $name
	 * @return InputObjectType
	 */
	public function input($name) {
		$name = strtolower($name);

		if ($this->has('definition', $name)) {
			return $this->get('definition', $name)->resolveInputType();
		}

		throw new Exception\TypeNotFoundException('Cannot find type ' . $name);
	}

	/**
	 * Return existing type as lifeOf
	 *
	 * @param  string $name
	 * @return ListOf
	 */
	public function listOf($name) {
		return Type::listOf($this->type($name));
	}

	/**
	 * Execute query
	 *
	 * @param  string $query
	 * @param  array  $variables
	 * @param  array  $opts
	 *
	 * @return array
	 */
	public function execute($query, $variables = [], $opts = []) {
		$root          = array_get($opts, 'root', null);
		$context       = array_get($opts, 'context', null);
		$schemaName    = array_get($opts, 'schema', null);
		$operation     = array_get($opts, 'operationName', null);
		$schema        = $this->getSchema($schemaName);
		$fieldResolver = function ($source, $args, $context, $info) {
			$result = Executor::defaultFieldResolver($source, $args, $context, $info);

			if (is_null($result)) {
				$result = data_get($source, snake_case($info->fieldName));
			}

			return $result;
		};

		return GraphQLBase::executeQuery($schema, $query, $root, $context, $variables, $operation, $fieldResolver)->toArray(true);
	}

	/**
	 * Manage query
	 *
	 * @param  string[] $queries
	 * @return array
	 */
	private function manageQuery(array $queries) {
		$data = [];

		foreach ($queries as $query) {
			$query = $this->make($query);
			$name  = $query->getName();
			$data[$name] = $query->resolveType();
		}

		return new ObjectType([
			'name'   => 'Query',
			'fields' => $this->applyTransformers(['list', 'view'], $data)
		]);
	}

	/**
	 * Manage mutation
	 *
	 * @param  array $mutations
	 * @return array
	 */
	private function manageMutation(array $mutations) {
		$data = [];

		foreach ($mutations as $mutation) {
			$mutation = $this->make($mutation);
			$name     = $mutation->getName();
			$data[$name] = $mutation->resolveType();
		}

		return new ObjectType([
			'name'   => 'Mutation',
			'fields' => $this->applyTransformers(['store', 'drop', 'batch', 'restore'], $data)
		]);
	}

	/**
	 * Register a schema
	 *
	 * @param  string $name
	 * @param  array  $data
	 *
	 * @return void
	 */
	public function registerSchema($name, array $data) {
		$this->save('schema', $name, array_merge([
			'query'    => [],
			'mutation' => [],
		], $data));
	}

	/**
	 * Register a definition
	 *
	 * @param  string $type
	 * @return void
	 */
	public function registerDefinition($definition) {
		$definition = $this->make($definition);

		if (!($definition instanceof Definition)) {
			throw new Exception\TypeException('Definition must inherits from ' . Definition::class);
		}

		$this->save('definition', strtolower($definition->getName()), $definition);
	}

	/**
	 * Apply transformation
	 *
	 * @param  array $default
	 * @return mixed
	 */
	private function applyTransformers(array $kinds, array $default = []) {
		// Convert string to instance if possible
		$definitions = $this->get('definition');

		foreach ($definitions as $definition) {
			$transformers = array_filter($definition->transformers);
			$appliers     = array_filter($definition->getTransformers());
			$appliers     = array_intersect(array_keys($appliers), $kinds);

			foreach ($appliers as $transformer) {
				if (array_key_exists($transformer, $transformers)) {
					$transformer = $this->make($transformers[$transformer]);
					$name = $transformer->getName($definition);

					$default[$name] = $transformer->transform($definition);
				}
			}
		}

		return $default;
	}

	/**
	 * Assert schema exists
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function hasSchema($name) {
		return $this->has('schema', $name);
	}

	/**
	 * Save data into the cache
	 *
	 * @param  string $namespace
	 * @param  string $key
	 * @param  mixed  $data
	 * @return bool
	 */
	private function save($namespace, $key, $data) {
		$item    = $this->cache->getItem(strtolower($namespace));
		$key     = strtolower($key);
		$content = (is_null($item->get())) ? [$key => []] : $item->get();
		$content[$key] = $data;

		$item->set($content);
		return $this->cache->save($item);
	}

	/**
	 * Check if cache has key within the namespace
	 *
	 * @param  string $namespace
	 * @param  string $key
	 * @return bool
	 */
	private function has($namespace, $key) {
		return array_key_exists($key, $this->get($namespace));
	}

	/**
	 * Return cache content
	 *
	 * @param  string $namespace
	 * @param  string $key
	 * @return mixed
	 */
	private function get($namespace, $key = null) {
		$data = $this->cache->getItem(strtolower($namespace))->get();
		$data = empty($data) ? [] : $data;

		return (is_null($key)) ? $data : $data[$key];
	}

	/**
	 * Make given string if possible
	 *
	 * @param  mixed $cls
	 * @return mixed
	 */
	private function make($cls) {
		return (is_string($cls)) ? $this->app->make($cls) : $cls;
	}
}
