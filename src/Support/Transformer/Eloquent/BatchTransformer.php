<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use GraphQL\Type\Definition\InputObjectType;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into create/update many mutation
 *
 * @see Transformer
 */
class BatchTransformer extends Transformer {
	/**
	 * Return mutation name
	 *
	 * @param  Definition $definition
	 * @return string
	 */
	public function getName(Definition $definition) {
		return str_plural(strtolower($definition->getName()));
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition) {
		return [
			'objects' => [
				'description' => 'Contains all objects to update or create',
				'type' => Type::nonNull(Type::listOf(new InputObjectType([
					'name' => sprintf('%sBatch', ucfirst($definition->getName())),
					'fields' => [
						'id' => ['type' => Type::id(), 'description' => 'Primary key lookup' ],
						'with' => [
							'type' => $definition->resolveInputType(),
							'description' => 'Contains updated fields'
						]
					]
				])))
			]
		];
	}

	/**
	 * {@overide}
	 *
	 * @param  Definition $definition
	 * @return \GraphQL\Type\Definition\ListOfType
	 */
	public function resolveType(Definition $definition) {
		return Type::listOf($definition->resolveType());
	}

	/**
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getResolver(array $opts) {
		$models = [];

		foreach ($opts['args']['objects'] as $args) {
			$model = $opts['source']->findOrNew(array_get($args, 'id', 0));
			$data = array_filter($args['with'], function ($key) use ($model) {
				return !method_exists($model, $key);
			}, ARRAY_FILTER_USE_KEY);

			$model->fill($data);
			$model->save();

			foreach (array_diff_key($args['with'], $data) as $column => $values) {
				$model->{$column}()->sync(array_filter($values));
			}

			array_push($models, $model);
		}

		return $models;
	}
}
