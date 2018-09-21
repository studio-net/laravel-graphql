<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Support\Transformer\EloquentTransformer;
use StudioNet\GraphQL\Support\Transformer\Eloquent\Relation\RelationTransformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use StudioNet\GraphQL\Error\ValidationError;

/**
 * Transform a Definition into create/update mutation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @see EloquentTransformer
 */
class StoreTransformer extends EloquentTransformer {
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
	 * {@inheritDoc}
	 * @return string
	 */
	public function getTransformerName(): string {
		return 'store';
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
		return parent::getArguments($definition) + [
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
			throw (new ValidationError('validation'))->setValidator($validator);
		}
	}

	/**
	 * @override
	 * TODO: Should be refactored
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	protected function getResolver(array $opts) {
		$model = parent::getResolver($opts);

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
				$opts['definition'],
				$model,
				$inputKey,
				$inputValue
			);

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
		$relationTransformers = [];
		foreach ($relationInput as $column => $values) {
			if (empty($values)) {
				// TODO: check if it's pertinent
				// empty values are ignored because, currently, nothing is deleted through nested update
				// it can be problematic because empty top level fields are emptied.
				continue;
			}
			$relationTransformer = Relation\RelationTransformerFactory::getTransformer(
				$model,
				$column,
				$values
			);
			$relationTransformer->transform();
			$relationTransformers[] = $relationTransformer;
		}

		$model->save();

		// Sync relations which need to be synced after save
		// TODO: it will be create to attach a callback to $model
		// to be fired at 'saved' event
		foreach ($relationTransformers as $relationTransformer) {
			$relationTransformer->afterSave();
		}

		// Apply post-save callBacks
		foreach ($savedCallbacks as $callBack) {
			call_user_func($callBack);
		}

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Builder $builder
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	protected function then(Builder $builder, array $opts) {
		return $builder->findOrNew(array_get($opts['args'], 'id', 0));
	}


	/**
	 * Applies the custom input callback, if it exists.
	 *
	 * Returns either:
	 * - false (if no callback exists)
	 * - the result of the callback (which could be an array)
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 *
	 * @param Definition $definition
	 * @param Model $model
	 * @param String $inputKey
	 * @param mixed $inputValue
	 * @return mixed
	 */
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
