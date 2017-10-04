<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into create/update mutation
 *
 * @see Transformer
 */
class StoreTransformer extends Transformer {
	/**
	 * Return mutation name
	 *
	 * @param  Definition $definition
	 * @return string
	 */
	public function getName(Definition $definition) {
		return strtolower($definition->getName());
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
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition) {
		return [
			'id'   => ['type' => Type::id(), 'description' => 'Primary key lookup' ],
			'with' => [
				'type' => $this->getInputType($definition),
				'description' => 'Contains updated fields'
			]
		];
	}

	/**
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return Model
	 */
	public function getResolver(array $opts) {
		$model = $opts['source']->findOrNew(array_get($opts['args'], 'id', 0));
		$data  = array_filter($opts['args']['with'], function($key) use ($model) {
			return !method_exists($model, $key);
		}, ARRAY_FILTER_USE_KEY);

		$model->fill($data);
		$model->save();

		foreach (array_diff_key($opts['args']['with'], $data) as $column => $values) {
			$model->{$column}()->sync(array_filter($values));
		}

		return $model;
	}
}
