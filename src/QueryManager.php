<?php
namespace StudioNet\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

class QueryManager {
	/** @var Application $app */
	private $app;

	/** @var array $cache */
	private $cache = [];

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
	 * fromEntity
	 *
	 * @param  Model $model
	 * @return ObjectType
	 */
	public function fromEntity(Model $model) {
		$singular  = str_singular($model->getTable());
		$pluralize = str_plural($model->getTable());

		return [
			$singular  => $this->buildSingular($model),
			$pluralize => $this->buildPlural($model),
		];
	}

	/**
	 * Build singular entity
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function buildSingular(Model $model) {
		return [
			'resolve' => $this->getResolver($model),
			'args'    => $this->getArguments(),
			'type'    => $this->getType($model)
		];
	}

	/**
	 * Build pluralize entity
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function buildPlural(Model $model) {
		return [
			'resolve' => $this->getResolver($model),
			'args'    => $this->getArguments(true),
			'type'    => GraphQLType::listOf($this->getType($model))
		];
	}

	/**
	 * Return availabled arguments
	 *
	 * @param  bool $plural
	 * @return array
	 */
	public function getArguments($plural = false) {
		if ($plural === false) {
			return [
				'id' => ['type' => GraphQLType::id(), 'description' => 'Primary key lookup']
			];
		}

		return [
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}

	/**
	 * Return ObjectType for given model
	 *
	 * @param  Model $model
	 * @param  array $depth
	 *
	 * @return ObjectType
	 */
	public function getType(Model $model, array $depth = []) {
		$relations = $this->getRelations($model);
		$columns   = $this->getColumns($model, $relations);
		$fields    = [
			'name'   => uniqid(),
			'fields' => []
		];

		foreach ($columns as $column) {
			$field = [];

			switch ($columns) {
				case 'id' : $type = GraphQLType::nonNull(GraphQL::id()); break;
				default   : $type = GraphQLType::string(); break;
			}

			// We have a relationship field here !
			if (array_key_exists($column, $relations)) {
				$relation = $relations[$column];

				// Manage depth in order to prevent a user with posts with user
				// with posts, etc.
				if (!empty($depth) and $this->assertMaximalDepth($model, $relation, $depth)) {
					continue;
				}

				$related   = $this->app->make($relation['model']);
				$pluralize = false;
				$depth     = $this->buildDepth($depth, $model, $relation);

				// Some relation can handle arguments. Other, none
				switch ($relation['type']) {
					case 'HasMany' : $pluralize = true; break;
				}

				// Only append arguments if not empty. Some relations like
				// `BelongsTo` doesn't handle arguments (we can't lookup throw
				// a single entry, even with id)
				if ($pluralize) {
					$type = GraphQLType::listOf($this->getType($related, $depth));
					$field['args']    = $this->getArguments(true);
					$field['resolve'] = $this->getChildResolver($relation);
				} else {
					$type = $this->getType($related, $depth);
				}

				unset($relations[$column]);
			}

			$fields['fields'][$column] = array_merge($field, [
				'description' => title_case(preg_replace('/_/', ' ', $column)),
				'type'        => $type
			]);
		}

		return new ObjectType($fields);
	}

	/**
	 * Assert that maximal depth is not already fetch. It prevent many problems
	 * like this schema :
	 *
	 * ```
	 * {
	 *    users {
	 *       posts {
	 *          author {
	 *              posts { # Will not be availabled
	 *                  ...
	 *              }
	 *          }
	 *       }
	 *    }
	 * }
	 * ```
	 *
	 * I think the system is not really perfect but it works at now...
	 *
	 * @param  Model $model
	 * @param  array $relation
	 * @param  array $depth
	 *
	 * @return bool
	 */
	public function assertMaximalDepth(Model $model, array $relation, array $depth) {
		$table = $model->getTable();

		if (array_key_exists($table, $depth)) {
			return in_array($relation['field'], $depth[$table]);
		}

		return false;
	}

	/**
	 * Build depth restriction
	 *
	 * @param  array $depth
	 * @param  Model $model
	 * @param  array $relation
	 *
	 * @return array
	 */
	public function buildDepth(array $depth, Model $model, array $relation) {
		$table = $model->getTable();

		if (!array_key_exists($table, $depth)) {
			$depth[$table] = [];
		}

		$depth[$table] = array_merge($depth[$table], [$relation['field']]);
		return $depth;
	}

	/**
	 * Return columns name for given model
	 *
	 * @param  Model $model
	 * @param  array $include
	 * @return array
	 */
	public function getColumns(Model $model, array $include = []) {
		$key = 'schema:' . get_class($model);

		if (empty($this->cache[$key])) {
			$columns = \Schema::getColumnListing($model->getTable());
			$columns = array_diff($columns, $model->getHidden());
			$columns = array_merge($columns, array_keys($include));

			$this->cache[$key] = $columns;
		}
		
		return $this->cache[$key];
	}

	/**
	 * Return relationships
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function getRelations(Model $model) {
		if (method_exists($model, 'relationships')) {
			return $model->relationships();
		}

		return [];
	}

	/**
	 * Return resolver for given model
	 *
	 * @param  Model $model
	 * @return callable
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function getResolver(Model $model) {
		$relations = $this->getRelations($model);

		return function($root, array $args, $context, ResolveInfo $info) use ($model, $relations) {
			$primary = $model->getKeyName();
			$builder = $model->newQuery();
			$fields  = $info->getFieldSelection($depth = 3);
			$common  = array_intersect_key($fields, $relations);

			if (!empty($common)) {
				foreach (array_keys($common) as $related) {
					$builder->with($related);
				}
			}

			// Retrieve single node
			if (array_key_exists('id', $args)) {
				return $builder->findOrFail($args['id']);
			}

			foreach ($args as $key => $value) {
				switch ($key) {
					case 'after'  : $builder->where($primary, '>', $value) ; break;
					case 'before' : $builder->where($primary, '<', $value) ; break;
					case 'skip'   : $builder->skip($value)                 ; break;
					case 'take'   : $builder->take($value)                 ; break;
				}
			}

			return $builder->get();
		};
	}

	/**
	 * Return a child resolver from given relation
	 *
	 * @param  array $relation
	 * @return callable
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getChildResolver(array $relation) {
		$method = $relation['field'];

		return function($root, array $args) use ($method) {
			$collection = $root->{$method};

			foreach ($args as $key => $value) {
				switch ($key) {
					case 'after'  : $collection = $collection->where($primary, '>', $value) ; break;
					case 'before' : $collection = $collection->where($primary, '<', $value) ; break;
					case 'skip'   : $collection = $collection->skip($value)                 ; break;
					case 'take'   : $collection = $collection->take($value)                 ; break;
				}
			}

			return $collection->all();
		};
	}
}
