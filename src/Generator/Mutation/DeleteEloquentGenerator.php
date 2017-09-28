<?php
namespace StudioNet\GraphQL\Generator\Mutation;

use StudioNet\GraphQL\Generator\Generator;
use StudioNet\GraphQL\Definition\Type\EloquentObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Model;

/**
 * DeleteEloquentGenerator
 *
 * @see Generator
 */
class DeleteEloquentGenerator extends Generator {
	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof EloquentObjectType);
	}

	/**
	 * {@inheritDoc}
	 */
	public function generate($instance) {
		return [
			'args'    => $this->getArguments(),
			'type'    => $instance,
			'resolve' => $this->getResolver($instance->getModel())
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKey($instance) {
		return strtolower('delete' . str_singular($instance->name));
	}

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	protected function getArguments() {
		return [
			'id' => ['type' => Type::nonNull(Type::id())]
		];
	}

	/**
	 * Resolve
	 *
	 * @param  Model $model
	 * @return Callable
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	protected function getResolver(Model $model) {
		return function($root, array $args) use ($model) {
			$primary = $model->getKeyName();
			$entity  = $model->findOrFail($args[$primary] ?: 0);
			$entity->delete();

			return $entity;
		};
	}
}

