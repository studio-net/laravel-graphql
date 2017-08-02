<?php
namespace StudioNet\GraphQL\Generator\Mutation;

use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use StudioNet\GraphQL\Generator\Generator;
use StudioNet\GraphQL\Type\EloquentObjectType;

/**
 * Generate singular query from Eloquent object type
 *
 * @see Generator
 */
class NodeEloquentGenerator extends Generator {
	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof EloquentObjectType);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getKey($instance) {
		return strtolower(str_singular($instance->name));
	}

	/**
	 * {@inheritDoc}
	 */
	public function generate($instance) {
		return [
			'args'    => $this->getArguments($instance->getModel()),
			'type'    => $instance,
			'resolve' => $this->getResolver($instance->getModel())
		];
	}

	/**
	 * Return availabled arguments from model reflection database fields
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function getArguments(Model $model) {
		$data     = [];
		$columns  = array_filter($model->getColumns());
		$fillable = array_flip($model->getFillable());
		$hidden   = array_flip($model->getHidden());

		if (!empty($fillable)) {
			$columns = array_intersect_key($columns, $fillable);
		}

		if (!empty($hidden)) {
			$columns = array_diff_key($columns, $hidden);
		}

		// Parse each column in order to know which is fillable. To allow
		// model to be updated, we have to use a uniq id : the id
		foreach ($columns as $column => $type) {
			$data[$column] = ['type' => $type];
		}

		return $data;
	}

	/**
	 * Resolve mutation
	 *
	 * @param  Model $model
	 * @return Model
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	protected function getResolver(Model $model) {
		return function($root, array $args) use ($model) {
			$primary = $model->getKeyName();
			$data    = $model->query()->updateOrCreate(
				[$primary => $args[$primary]],
				$args
			);

			return $data;
		};
	}
}
