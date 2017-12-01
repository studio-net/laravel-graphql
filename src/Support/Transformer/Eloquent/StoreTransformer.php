<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
				'type' => $definition->resolveInputType(),
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
		$data  = array_filter($opts['args']['with'], function ($value, $key) use ($model) {
			return !(is_array($value) and method_exists($model, $key));
		}, ARRAY_FILTER_USE_BOTH);

		$model->fill($data);
		$model->save();

		foreach (array_diff_key($opts['args']['with'], $data) as $column => $values) {
			$relation = $model->{$column}();

			// If we are on a hasOne relationship, we have to manage the
			// firstOrNew case
			//
			// https://laracasts.com/discuss/channels/general-discussion/hasone-create-duplicates
			if (get_class($relation) === HasOne::class) {
				$relation->firstOrNew([])->fill($values)->save();
			}

			else {
				if (!is_array(array_first($values))) {
					$values = [$values];
				}

				// For each relationship, find or new by id and fill with data
				foreach ($values as $value) {
					$relation->findOrNew(array_get($values, 'id', null))->fill($value)->save();
				}
			}
		}

		return $model;
	}
}
