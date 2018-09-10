<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use StudioNet\GraphQL\Support\Transformer\EloquentTransformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into drop mutation
 *
 * @see EloquentTransformer
 */
class DropTransformer extends EloquentTransformer {
	/**
	 * Return mutation name
	 *
	 * @param  Definition $definition
	 * @return string
	 */
	public function getName(Definition $definition) {
		return sprintf('delete%s', ucfirst(strtolower($definition->getName())));
	}

	/**
	 * {@inheritDoc}
	 * @return string
	 */
	public function getTransformerName(): string {
		return 'drop';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition) {
		$args = parent::getArguments($definition);
		$traits = class_uses($definition->getSource());

		if (in_array(SoftDeletes::class, $traits)) {
			$args = $args + [
				'force' => ['type' => Type::bool(), 'description' => 'Force deletion']
			];
		}

		return $args + [
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
	 * @override
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function getResolver(array $opts) {
		$model = parent::getResolver($opts);

		if (array_get($opts['args'], 'force', false)) {
			$model->forceDelete();
		} else {
			$model->delete();
		}

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
		return $builder->findOrFail(array_get($opts['args'], 'id'));
	}
}
