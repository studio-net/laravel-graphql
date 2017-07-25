<?php
namespace StudioNet\GraphQL;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Type\TypeInterface;

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
		$data       = [
			'data' => $result->data
		];

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
	 * @param  array  $queries
	 * @return array
	 */
	public function manageQuery($name, array $queries) {
		// TODO
		// Manage custom queries

		if (config('graphql.types_as_query')) {
			$schemas = config('graphql.types_in_schemas', 'all');

			if ($schemas === 'all' or in_array($name, $schemas)) {
				$types   = $this->getTypes();
				$queries = array_merge($queries, $types);
			}
		}

		return new ObjectType([
			'name'   => 'Query',
			'fields' => $queries
		]);
	}

	/**
	 * Return available types for query
	 *
	 * @return array
	 */
	public function getTypes() {
		$types = $this->types;
		$query = [];

		foreach ($types as $type) {
			foreach ([false, true] as $plural) {
				$instance = $this->type($type, $plural);
				$name     = $instance->name;
				$config   = $instance->config;
				$value    = ($plural) ? GraphQLType::listOf($instance) : $instance;

				$query[$name] = [
					'type'    => $value,
					'args'    => $config['args'],
					'resolve' => $config['resolve']
				];
			}
		}

		return $query;
	}

	/**
	 * Return type based on the class name
	 *
	 * @param  string|TypeInterface $cls
	 * @param  bool $plural
	 *
	 * @return ObjectType
	 */
	public function type($type, $plural = false) {
		if (!$type instanceof TypeInterface) {
			if (!array_key_exists($cls, $this->types)) {
				return null;
			}

			$type = $this->types[$cls];
		}

		$name   = $type->getName();
		$args   = $type->getArguments();
		$fields = $type->getBuiltFields();
		$desc   = $type->getDescription();

		if ($plural) {
			$name = str_plural($name);
			$args = array_except($args, ['id']);
		} else {
			$args = array_only($args, ['id']);
		}

		return new ObjectType([
			'name'        => $name,
			'description' => $desc,
			'fields'      => $fields,
			'args'        => $args,
			'resolve'     => [$type, 'resolve']
		]);
	}

	/**
	 * TODO
	 *
	 * @param  string $name
	 * @param  array  $mutations
	 * @return array
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
		$this->schemas[$name] = $data;
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
