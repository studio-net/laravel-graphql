<?php
namespace StudioNet\GraphQL\Eloquent;

use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\ObjectType;
use Illuminate\Database\Eloquent\Model;

class TypeManager extends Manager {
	/**
	 * Return an array of ObjectType based on model
	 *
	 * @param  array $models
	 * @return ObjectType[]
	 */
	public function fromModels(array $models) {
		$types = [];

		// Manage type-based queries
		foreach ($models as $key => $model) {
			$model = $this->app->make($model);

			// If user doesn't specified a key to access entity, simply use
			// entity's table name
			if (is_numeric($key)) {
				$key = str_singular($model->getTable());
			}

			$types[$key] = $this->toType($model);
		}

		return $types;
	}

	/**
	 * Convert a Model to an ObjectType
	 *
	 * @param  Model $model
	 * @param  array $depth
	 * @return ObjectType
	 */
	public function toType(Model $model, array $depth = []) {
		$relations = $this->getRelations($model);
		$columns   = $this->getColumns($model, $relations);
		$fields    = [
			'name'   => uniqid(),
			'fields' => [],
			'model'  => $model
		];

		foreach ($columns as $column => $type) {
			$field = [];

			// We have a relationship field here !
			if (array_key_exists($column, $relations)) {
				$relation  = $relations[$column];
				$related   = $this->app->make($relation['model']);
				$table     = $related->getTable();
				$pluralize = false;

				// The main goal here is to prevent infinite depth : if a model
				// `User` have to `hasMany` to `Post` and `Post` have a
				// `BelongsTo` relation to `User`, we could create request like
				// 
				// ```graphql
				// query {
				//    users {
				//       posts {
				//          user { <- stop here
				//              posts {
				//                  ...
				//              }
				//          }
				//       }
				//    }
				// }
				// ```
				if (array_key_exists($table, $depth)) {
					if (in_array($relation['field'], $depth[$table])) {
						continue;
					}
				}

				// Create empty array
				else {
					$depth[$table] = [];
				}

				$depth[$table] = array_merge($depth[$table], [$relation['field']]);
				$type = $this->toType($related, $depth);

				// Build relationship : how to know if we have to return a
				// listOf or directly the type ? With the known relationship !
				// If we have a `HasMany` relationship, we're able to know that
				// we have to return many type at once
				switch ($relation['type']) {
					case 'HasMany' : $pluralize = true; break;
				}

				// Only append arguments if not empty. Some relations like
				// `BelongsTo` doesn't handle arguments (we can't lookup throw
				// a single entry, even with id)
				if ($pluralize) {
					$type  = GraphQLType::listOf($type);
					$field = array_merge([
						'args'    => $this->getArguments(true),
						'resolve' => $this->getResolver($relation)
					], $field);
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
	 * Return a type resolver from given relation
	 *
	 * @param  array $relation
	 * @return callable
	 */
	private function getResolver(array $relation) {
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
