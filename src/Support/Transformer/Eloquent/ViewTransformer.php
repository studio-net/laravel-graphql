<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;

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
			'id' => ['type' => Type::nonNull(Type::id()), 'description' => 'Primary key lookup']
		];
	}

	/**
	 * {@overide}
	 *
	 * @param  Definition $definition
	 * @return ListOf
	 */
	public function resolveType(Definition $definition) {
		return $definition->resolveType();
	}

	/**
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return Model
	 */
	public function getResolver(array $opts) {
		return $opts['source']
			->newQuery()
			->with($opts['with'])
			->findOrFail(array_get($opts['args'], 'id', 0));
	}
}
