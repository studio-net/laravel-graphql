<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into restore mutation
 *
 * @see Transformer
 */
class RestoreTransformer extends Transformer {
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
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition) {
		return [
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
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getResolver(array $opts) {
		$model = $opts['source']->withTrashed()->findOrFail(array_get($opts['args'], 'id', 0));
		$model->restore();

		return $model;
	}
}
