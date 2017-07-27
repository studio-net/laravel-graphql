<?php
namespace StudioNet\GraphQL;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Support\Interfaces\TypeInterface;

class GraphQL {
	/** @var Application $app */
	private $app;

	/** @var array $schemas */
	private $schemas = [];

	/** @var TypeInterface[] $types */
	private $types = [];

	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @return void
	 */
	public function __construct(Application $app) {
		$this->app = $app;
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
		$schema = $this->schemas[$name];
		$schema = array_merge($schema, [
			'query'    => [],
			'mutation' => []
		]);

		// Compute query and mutation fields
		$schema['query']    = $this->manageQuery($name, $schema['query']);
		$schema['mutation'] = $this->manageMutation($name, $schema['mutation']);

		return new Schema($schema);
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
		$root       = array_get($opts, 'root', null);
		$context    = array_get($opts, 'context', null);
		$schemaName = array_get($opts, 'schema', null);
		$operation  = array_get($opts, 'operationName', null);
		$schema     = $this->getSchema($schemaName);
		$result     = GraphQLBase::executeAndReturnResult($schema, $query, $root, $context, $variables, $operation);
		$data       = ['data' => $result->data];

		if (!empty($result->errors)) {
			$data['errors'] = $result->errors;
		}

		return $data;
	}

	/**
	 * Manage query : load all queries and also append type-based queries if
	 * allowed
	 *
	 * @param  string $name
	 * @param  TypeInterface[] $queries
	 * @return array
	 */
	public function manageQuery($name, array $queries) {
		// Manage custom queries
		foreach ($queries as $query) {
			$name    = $query->getName();
			$queries = array_merge($queries, $query->toType());
		}

		$entities = config('graphql.type.entities', []) + $this->schemas[$name]['entities'];

		// Manage type-based queries
		foreach ($entities as $entity) {
			$entity  = $this->app->make($entity);
			$query   = $this->app->make('graphql.query_manager')->fromEntity($entity);
			$queries = array_merge($query, $queries);
		}

		return new ObjectType([
			'name'   => 'Query',
			'fields' => $queries
		]);
	}

	/**
	 * TODO
	 *
	 * @param  string $name
	 * @param  array  $mutations
	 * @return array
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function manageMutation($name, array $mutations) {
		return $mutations;
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
		$this->schemas[$name] = array_merge([
			'query'    => [],
			'mutation' => [],
			'entities' => []
		], $data);
	}

	/**
	 * Register a type
	 *
	 * @param  string|TypeInterface $type
	 * @return void
	 */
	public function registerType($type) {
		if (!$type instanceof TypeInterface) {
			if (!class_exists($type)) {
				throw new Exception\TypeNotFoundException('Cannot find type ' . $type);
			}

			$type = $this->app->make($type);
		}

		$key = get_class($type);
		$this->types[$key] = $type;
	}

	/**
	 * Assert schema exists
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function hasSchema($name) {
		return array_key_exists($name, $this->schemas);
	}
}
