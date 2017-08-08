<?php
namespace StudioNet\GraphQL\Generator\Query;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use StudioNet\GraphQL\Generator\Generator;
use StudioNet\GraphQL\Definition\Type\EloquentObjectType;

/**
 * Generate meta query for given EloquentObjectType
 *
 * @see Generator
 */
class MetaEloquentGenerator extends Generator {
	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof EloquentObjectType);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKey($instance) {
		return sprintf('_%s_meta', strtolower(str_plural($instance->name)));
	}

	/**
	 * {@inheritDoc}
	 */
	public function dependsOn() {
		return ['meta'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function generate($instance) {
		return [
			'type'    => $this->app['graphql']->type('meta'),
			'resolve' => $this->getResolver($instance->getModel())
		];
	}

	/**
	 * Return meta resolver
	 *
	 * @param  Model $model
	 * @return callable
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function getResolver(Model $model) {
		return function($root, array $args, $context, ResolveInfo $info) use ($model) {
			// Clone collection in order to not erase existin collection
			$collection = $model->newQuery();
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
}
