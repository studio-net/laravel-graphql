<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use StudioNet\GraphQL\Support\Transformer\EloquentTransformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into query view
 *
 * @see Transformer
 */
class ViewTransformer extends EloquentTransformer {
	/**
	 * Return query name
	 *
	 * @param  Definition $definition
	 * @return string
	 */
	public function getName(Definition $definition) {
		return strtolower($definition->getName());
	}

	/**
	 * {@inheritDoc}
	 * @return string
	 */
	public function getTransformerName(): string {
		return 'view';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getArguments(Definition $definition) {
		return parent::getArguments($definition) + [
			'id' => ['type' => Type::nonNull(Type::id()), 'description' => 'Primary key lookup'],
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
	 * Return fetchable node resolver
	 *
	 * @param  Builder $builder
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function then(Builder $builder, array $opts) {
		$builder = $builder->with($opts['with']);

		// Appends trashed on `SoftDeletes` source traits
		if (in_array(SoftDeletes::class, class_uses($opts['source']))) {
			$builder->withTrashed();
		}

		return $builder->findOrFail(array_get($opts['args'], 'id'));
	}
}
