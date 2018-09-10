<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Support\Transformer\EloquentTransformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into restore mutation
 *
 * @see EloquentTransformer
 */
class RestoreTransformer extends EloquentTransformer {
	/**
	 * Return mutation name
	 *
	 * @param  Definition $definition
	 * @return string
	 */
	public function getName(Definition $definition) {
		return sprintf('restore%s', ucfirst(strtolower($definition->getName())));
	}

	/**
	 * {@inheritDoc}
	 * @return string
	 */
	public function getTransformerName(): string {
		return 'restore';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition) {
		return parent::getArguments($definition) + [
			'id' => ['type' => Type::nonNull(Type::id()), 'description' => 'Primary key lookup' ]
		];
	}

	/**
	 * {@overide}
	 *
	 * @param  Definition $definition
	 * @return \GraphQL\Type\Definition\ObjectType
	 */
	public function resolveType(Definition $definition) {
		return $definition->resolveType();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function getResolver(array $opts) {
		$model = parent::getResolver($opts);
		$model->restore();

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Builder $builder
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function then(Builder $builder, array $opts) {
		return $builder->withTrashed()->findOrFail(array_get($opts['args'], 'id'));
	}
}
