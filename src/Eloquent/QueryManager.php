<?php
namespace StudioNet\GraphQL\Eloquent;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;

class QueryManager extends Manager {
	/**
	 * Return singular and plural of given type
	 *
	 * @param  string $table
	 * @param  ObjectType $type
	 * @return array
	 */
	public function fromType($table, ObjectType $type) {
		$singular = str_singular($table);
		$plural   = str_plural($table);

		return [
			$singular => $this->toSingle($type),
			$plural   => $this->toMany($type)
		];
	}

	/**
	 * Return a query that will return a single ObjectType
	 *
	 * @param  ObjectType $type
	 * @return array
	 */
	private function toSingle(ObjectType $type) {
		$model = $type->config['model'];

		return [
			'resolve' => $this->getResolver($model),
			'args'    => $this->getArguments(),
			'type'    => $type
		];
	}

	/**
	 * Return a query that will return a ListOf
	 *
	 * @param  ObjectType $type
	 * @return array
	 */
	private function toMany(ObjectType $type) {
		$model = $type->config['model'];

		return [
			'resolve' => $this->getResolver($model),
			'args'    => $this->getArguments(true),
			'type'    => GraphQLType::listOf($type)
		];
	}

	/**
	 * Return resolver for given model
	 *
	 * @param  Model $model
	 * @return callable
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	private function getResolver(Model $model) {
		$relations = $this->getRelations($model);

		return function($root, array $args, $context, ResolveInfo $info) use ($model, $relations) {
			$primary = $model->getKeyName();
			$builder = $model->newQuery();
			$fields  = $info->getFieldSelection(3);
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

}
