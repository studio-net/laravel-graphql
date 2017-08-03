<?php
namespace StudioNet\GraphQL\Generator;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;

/**
 * EloquentGenerator
 *
 * @see EloquentGeneratorInterface
 * @see Generator
 * @abstract
 */
abstract class EloquentGenerator extends Generator implements EloquentGeneratorInterface {
	/**
	 * {@inheritDoc}
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function getResolver(Model $model) {
		return function($root, array $args, $context, ResolveInfo $info) use ($model) {
			$primary = $model->getKeyName();
			$builder = $model->newQuery();
			$fields  = $info->getFieldSelection(3);
			$common  = array_intersect_key($fields, $model->getRelationship());

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
				case 'after'  : $builder = $builder->where($primary, '>', $value) ; break;
				case 'before' : $builder = $builder->where($primary, '<', $value) ; break;
				case 'skip'   : $builder = $builder->skip($value)                 ; break;
				case 'take'   : $builder = $builder->take($value)                 ; break;
				}
			}

			return $builder->get();
		};
	}
}
