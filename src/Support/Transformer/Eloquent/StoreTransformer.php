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

		// Create or update model by ID
		$model = $opts['source']->findOrNew(array_get($opts['args'], 'id', 0));

		$relationInput = [];
		$savedCallbacks = [];

		// Filter input 'with', excluding some specific fields
		$data = [];
		foreach ($opts['args']['with'] as $inputKey => $inputValue) {
			// If the model has a method with the same name, and the input value is
			// an array, we'll manage it as a relation, later.
			if (method_exists($model, $inputKey)) {
				if (is_array($inputValue) or is_null($inputValue)) {
					$relationInput[$inputKey] = $inputValue;
					continue;
				}
			}

			// If the definition has an "inputFooBarField" method, use it.
			$callBackResult = $this->applyInputCallback(
				$opts['definition'], $model, $inputKey, $inputValue);

			// The callback returned an array, check for "saved"
			if ($callBackResult !== false) {
				if (is_array($callBackResult)) {
					if (isset($callBackResult['saved'])) {
						$savedCallbacks[] = $callBackResult['saved'];
					}
				}
				// And ignore this field, it's managed by the call back.
				continue;
			}

			// Simple Model mapping.
			$data[$inputKey] = $inputValue;
		}

		$this->validate($data, $opts['rules']);
		$model->fill($data);

		foreach ($relationInput as $column => $values) {
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
			} else if ($relationType === Relations\MorphTo::class) {
				$id = array_get($values, 'id', null);
				$type = array_get($values, '__typename', null);

				if (is_null($type)) {
					throw new Exception(
						"Can't update polymorphic relation without specify type");
				}

				// TODO: maybe there is a smarter way to guess type
				$className = '\App\\' . $type;
				if (!class_exists($className)) {
					throw new Exception("Unknown $className type");
				}

				$dep = $className::findOrNew($id);
				$dep->fill($values)->save();
				$relation->associate($dep);

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

		// Apply post-save callBacks
		foreach ($savedCallbacks as $callBack) {
			call_user_func($callBack);
		}

		return $model;

	}


	private function applyInputCallback($definition, $model, $inputKey, $inputValue) {

		$methodName = sprintf('input%sField', ucfirst(camel_case($inputKey)));
		$callBack = [$definition, $methodName];

		if (!is_callable($callBack)) {
			return false;
		}

		$result = call_user_func_array($callBack, [$model, $inputValue]);

		return $result;

	}

}
