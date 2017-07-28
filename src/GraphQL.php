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

		// This method is called only when `execute()` method is called. So, we
		// can initialize all our entities right here without problems
		$manager = $this->app->make('graphql.eloquent.type_manager');
		$models  = config('graphql.type.entities', []);
		$this->types = $this->types + $manager->fromModels($models);

		// Represents an array like
		//
		// [
		//    'query'    => [],
		//    'mutation' => []
		// ]
		$schema = $this->schemas[$name];

		// Compute query and mutation fields
		$schema['query']    = $this->manageQuery($schema['query']);
		$schema['mutation'] = $this->manageMutation($name, $schema['mutation']);

		return new Schema($schema);
	}

	/**
	 * Return existing type
	 *
	 * @param  string $name
	 * @return ObjectType
	 */
	public function type($name) {
		if (array_key_exists($name, $this->types)) {
			return $this->types[$name];
		}

		throw new TypeNotFoundException('Cannot find type ' . $name);
	}

	/**
	 * Return existing type as lifeOf
	 *
	 * @param  string $name
	 * @return ListOf
	 */
	public function listOf($name) {
		return GraphQLType::listOf($this->type($name));
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

		$schema = $this->getSchema($schemaName);
		$result = GraphQLBase::executeAndReturnResult($schema, $query, $root, $context, $variables, $operation);
		$data   = ['data' => $result->data];

		if (!empty($result->errors)) {
			$data['errors'] = $result->errors;
		}

		return $data;
	}

	/**
	 * Manage query : load all queries and also append type-based queries if
	 * allowed
	 *
	 * @param  TypeInterface[] $queries
	 * @return array
	 */
	public function manageQuery(array $queries) {
		$data    = [];
		$models  = config('graphql.type.entities', []);
		$manager = $this->app->make('graphql.eloquent.query_manager');

		foreach ($models as $model) {
			$table = str_singular($this->app->make($model)->getTable());
			$type  = $this->type($table);
			$data  = $data + $manager->fromType($table, $type);
		}

		return new ObjectType([
			'name'   => 'Query',
			'fields' => $data
		]);
	}

	/**
	 * TODO
	 *
	 * @param  array $mutations
	 * @return array
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function manageMutation(array $mutations) {
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
			'entities' => [],
			'type'     => []
		], $data);
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
