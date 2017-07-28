<?php
namespace StudioNet\GraphQL;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Support\FieldInterface;
use StudioNet\GraphQL\Support\TypeInterface;

/**
 * GraphQL implementation singleton
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
		//
		// TODO I don't really like to see this here... Must be refactored later
		$manager = $this->app->make('graphql.eloquent.type_manager');
		$models  = config('graphql.type.entities', []);

		foreach ($manager->fromModels($models) as $key => $type) {
			$this->registerType($key, $type);
		}

		// Represents an array like
		//
		// [
		//    'query'    => [],
		//    'mutation' => []
		// ]
		$schema = $this->schemas[$name];

		// Compute query and mutation fields
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

		if (array_key_exists($name, $this->types)) {
			return $this->types[$name];
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
		$schema     = $this->getSchema($schemaName);
		$result     = GraphQLBase::executeAndReturnResult($schema, $query, $root, $context, $variables, $operation);
		$data       = ['data' => $result->data];

		if (!empty($result->errors)) {
			$data['errors'] = $result->errors;
		}

		return $data;
	}

	/**
	 * Manage query
	 *
	 * @param  string[] $queries
	 * @return array
	 */
	public function manageQuery(array $queries) {
		$data    = [];
		$models  = config('graphql.type.entities', []);
		$manager = $this->app->make('graphql.eloquent.query_manager');

		// Parse each query class and build it within the ObjectType
		foreach ($queries as $name => $query) {
			if (is_numeric($name)) {
				$name = strtolower(with(new \ReflectionClass($query))->getShortName());
			}

			$query = $this->app->make($query);
			$data  = $data + [$name => $query->toArray()];
		}

		// Parse each model, retrieve is corresponding generated type and build
		// a generic query upon it
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
		$data    = [];
		$models  = config('graphql.type.entities', []);
		$manager = $this->app->make('graphql.eloquent.mutation_manager');

		// Parse each query class and build it within the ObjectType
		foreach ($mutations as $name => $mutation) {
			if (is_numeric($name)) {
				$name = strtolower(with(new \ReflectionClass($mutation))->getShortName());
			}

			$mutation = $this->app->make($mutation);
			$data = $data + [$name => $mutation->toArray()];
		}

		// Parse each model, retrieve is corresponding generated type and build
		// a generic mutation upon it
		foreach ($models as $model) {
			$table = str_singular($this->app->make($model)->getTable());
			$type  = $this->type($table);
			$data[$table] = $manager->fromType($type);
		}

		return new ObjectType([
			'name'   => 'Mutation',
			'fields' => $data
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
		$this->schemas[$name] = array_merge([
			'query'    => [],
			'mutation' => [],
			'entities' => [],
		], $data);
	}

	/**
	 * Register a type
	 *
	 * @param  string|null $name
	 * @param  string|ObjectType|TypeInterface $type
	 *
	 * @return void
	 */
	public function registerType($name, $type) {
		if (is_string($type)) {
			$type = $this->app->make($type);
		}

		// Assert that the given type extend from TypeInterface or is an
		// instance of ObjectType
		if ((!$type instanceof ObjectType) and (!$type instanceof TypeInterface)) {
			throw new Exception\TypeException('Given type doesn\'t extend from TypeInterface');
		}

		// Assert name is not empty : otherwise, get the class name from type
		if (empty($name) or is_numeric($name)) {
			$name = with(new \ReflectionClass($type))->getShortName();
		}

		// If the type is extended from TypeInterface, we know that he has a
		// `toType` method : so let's call it in order to retrieve an ObjectType
		if ($type instanceof TypeInterface) {
			$type = $type->toType();
		}

		// As we're working with generated types, we can't allow override
		// because user will be lost. So let's throw an exception when this
		// case is here
		if (array_key_exists($name, $this->types)) {
			throw new Exception\TypeException('Cannot override existing type');
		}

		$this->types[strtolower($name)] = $type;
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
