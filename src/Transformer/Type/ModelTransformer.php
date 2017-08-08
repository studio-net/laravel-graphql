<?php
namespace StudioNet\GraphQL\Transformer\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Support\Eloquent\ModelAttributes;
use StudioNet\GraphQL\Transformer\Transformer;
use StudioNet\GraphQL\Definition\Type\EloquentObjectType;

/**
 * Convert a Model instance to an EloquentObjectType
 *
 * @see Transformer
 */
class ModelTransformer extends Transformer {
	/** @var array $cache */
	private $cache = [];

	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof Model);
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform($instance) {
		$key = 'type:' . $instance->getTable();

		if (empty($this->cache[$key])) {
			$this->cache[$key] = new EloquentObjectType([
				'name'        => $this->getName($instance),
				'description' => $this->getDescription($instance),
				'fields'      => $this->getFields($instance),
				'model'       => $instance
			]);
		}

		return $this->cache[$key];
	}

	/**
	 * Return name of given model
	 *
	 * @param  Model $model
	 * @return string
	 */
	private function getName(Model $model) {
		if ($model instanceof ModelAttributes) {
			return $model->getObjectName();
		}

		return ucfirst(with(new \ReflectionClass($model))->getShortName());
	}

	/**
	 * Return model description
	 *
	 * @param  Model $model
	 * @return string
	 */
	private function getDescription(Model $model) {
		if ($model instanceof ModelAttributes) {
			return $model->getObjectDescription();
		}

		return sprintf('A %s model representation', $this->getName($model));
	}

	/**
	 * Return corresponding fields. We're prefer using callable here because of
	 * recursive models. As this method handles relationships, we have to manage
	 * all depths cases
	 *
	 * @param  Model $model
	 * @return callable
	 * @see    github.com/webonyx/graphql-php/blob/master/docs/type-system/object-types.md#field-configuration-options
	 */
	private function getFields(Model $model) {
		$attributes = $this->app->make(ModelAttributes::class);
		$columns    = $attributes->getColumns($model);
		$relations  = $attributes->getRelations($model);

		return function() use ($model, $columns, $relations) {
			$fields = [];

			foreach ($columns as $column => $type) {
				$field = [
					'name' => $column,
					'description' => title_case(preg_replace('/_/', ' ', $column)),
				];

				// We have a relationship field here !
				if (array_key_exists($column, $relations)) {
					// Get relationship
					$relation = $relations[$column];
					$related  = $this->app->make($relation['model']);

					// Get related type (if doesn't exists, it will be
					// generated)
					$many = false;
					$type = $this->transform($related);

					// Build relationship : how to know if we have to return
					// a listOf or directly the type ? With the known
					// relationship !  If we have a `HasMany` relationship,
					// we're able to know that we have to return many type
					// at once
					switch ($relation['type']) {
						case 'HasMany' : $many = true; break;
					}

					// Only append arguments if not empty. Some relations
					// like `BelongsTo` doesn't handle arguments (we can't
					// lookup throw a single entry, even with id)
					if ($many) {
						if ($this->hasMeta()) {
							// Create a field named `_{column}_meta` in order to
							// access info from it (like global count)
							$fields[] = [
								'name'        => "_{$column}_meta",
								'description' => "{$column} metadata",
								'type'        => $this->getMeta(),
								'resolve'     => $this->getMetaResolver($relation)
							];
						}

						// Continue throw process
						$type  = GraphQLType::listOf($type);
						$field = array_merge([
							'args'    => $this->getArguments(),
							'resolve' => $this->getResolver($relation)
						], $field);
					}

					unset($relations[$column]);
				}

				// If the value still null, we can't use it : just continue
				// without doing anything
				if (is_null($type)) {
					continue;
				}

				// Apply modifications into global fields array
				$fields[] = ['type' => $type] + $field;
			}

			return $fields;
		};
	}

	/**
	 * Check if meta is global registered
	 *
	 * @return bool
	 */
	private function hasMeta() {
		try {
			return (bool) $this->app['graphql']->type('meta');
		} catch (\Exception $e) {}

		return false;
	}

	/**
	 * Return meta type
	 *
	 * @return ObjectType
	 */
	private function getMeta() {
		return $this->app['graphql']->type('meta');
	}

	/**
	 * Resolve a relationship field
	 *
	 * @param  array $relation
	 * @return callable
	 */
	private function getResolver(array $relation) {
		$method  = $relation['field'];
		$primary = $this->app->make($relation['model'])->getKeyName();
		$order   = array_flip(array_keys($this->getArguments()));

		return function($root, array $args) use ($method, $primary, $order) {
			// Clone collection in order to not erase existin collection
			$collection = clone $root->{$method};

			// We have to specific custom order to args because we cannot take
			// some elements before splicing it...
			$order = array_intersect_key($order, $args);
			$args  = array_merge($order, $args);

			foreach ($args as $key => $value) {
				switch ($key) {
					case 'after'  : $collection = $collection->where($primary, '>', $value) ; break;
					case 'before' : $collection = $collection->where($primary, '<', $value) ; break;
					case 'skip'   : $collection = $collection->splice($value)               ; break;
					case 'take'   : $collection = $collection->take($value)                 ; break;
				}
			}

			return $collection->all();
		};
	}

	/**
	 * Resolve a meta field
	 *
	 * @param  array $relation
	 * @return callable
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	private function getMetaResolver(array $relation) {
		$method  = $relation['field'];

		return function($root, array $args, $context, ResolveInfo $info) use ($method) {
			// Clone collection in order to not erase existin collection
			$collection = clone $root->{$method}();
			$fields     = $info->getFieldSelection(3);
			$data       = [];

			foreach (array_keys($fields) as $key) {
				switch ($key) {
					case 'count' : $data['count'] = $collection->count(); break;
				}
			}

			return $data;
		};
	}

	/**
	 * Return available arguments (many because there's no argument for single
	 * element). Order matter
	 *
	 * @return array
	 */
	private function getArguments() {
		return [
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}
}
