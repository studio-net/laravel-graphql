<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Support\Facades\Validator;
use StudioNet\GraphQL\Error\ValidationError;

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
	 * @return \GraphQL\Type\Definition\ObjectType
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
			'id' => ['type' => Type::id(), 'description' => 'Primary key lookup' ],
			'with' => [
				'type' => $definition->resolveInputType(),
				'description' => 'Contains updated fields'
			]
		];
	}

	/**
	 * Validate data
	 *
	 * @param  array $data
	 * @param  array $rules
	 * @return void
	 */
	protected function validate(array $data, array $rules) {
		$validator = Validator::make($data, $rules);

		if ($validator->fails()) {
			throw with(new ValidationError('validation'))->setValidator($validator);
		}
	}

	/**
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getResolver(array $opts) {
		$model = $opts['source']->findOrNew(array_get($opts['args'], 'id', 0));
		$data = array_filter($opts['args']['with'], function ($value, $key) use ($model) {
			return !((is_array($value) or is_null($value)) and method_exists($model, $key));
		}, ARRAY_FILTER_USE_BOTH);

		$this->validate($data, $opts['rules']);
		$model->fill($data);

		foreach (array_diff_key($opts['args']['with'], $data) as $column => $values) {
			if (empty($values)) {
				// TODO: check if it's pertinent
				// empty values are ignored because, currently, nothing is deleted through nested update
				// it can be problematic because empty top level fields are emptied.
				continue;
			}
			
			$relation = $model->{$column}();

			// If we are on a hasOne or belongsTo relationship, we have to
			// manage the firstOrNew case
			//
			// https://laracasts.com/discuss/channels/general-discussion/hasone-create-duplicates
			$relationType = get_class($relation);
			if (in_array($relationType, [Relations\HasOne::class, Relations\BelongsTo::class])) {
				$dep = $relation->getRelated()->findOrNew(array_get($values, 'id', null));
				
				if (empty($dep->id)) {
					$dep = $relation->firstOrNew([]);
				}
				$dep->fill($values)->save();
				
				switch ($relationType) {
					case Relations\BelongsTo::class:
						$relation->associate($dep);
						break;
					default:
						$relation->save($dep);
				}
			} else {
				if (!is_array(array_first($values))) {
					$values = [$values];
				}

				if ($relationType === Relations\BelongsToMany::class) {
					$toKeep = array_map(function ($value) {
						return array_get($value, 'id', null);
					}, $values);
					
					$relation = $model->{$column}();
					$relation->sync(array_filter($toKeep, function ($value) {
						return !is_null($value);
					}));
				} else {
					// For each relationship, find or new by id and fill with data
					foreach ($values as $value) {
						// TODO: refactor
						// $relation is reset because findOrNew updates it and where
						// clauses are stacked.
						$relation = $model->{$column}();
						$entity = $relation->findOrNew(array_get($value, 'id', null));
						$fill = [];

						foreach (array_keys($value) as $key) {
							if ($entity->isFillable($key)) {
								$fill[$key] = $value[$key];
							}
						}
						$entity->fill($fill)->save();
					}
				}
			}
		}
		$model->save();

		return $model;
	}
}
