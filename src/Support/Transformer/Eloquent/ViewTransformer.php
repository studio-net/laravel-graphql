<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transform a Definition into query view
 *
 * @see Transformer
 */
class ViewTransformer extends Transformer {
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
	 *
	 * @param  Definition $definition
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getArguments(Definition $definition) {
		return [
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
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getResolver(array $opts) {
		$builder = $opts['source']->newQuery()->with($opts['with']);

		if (in_array(SoftDeletes::class, class_uses($opts['source']))) {
			$builder = $builder->withTrashed();
		}

		return $builder->findOrFail(array_get($opts['args'], 'id', 0));
	}
}
