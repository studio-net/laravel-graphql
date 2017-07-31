<?php
namespace StudioNet\GraphQL\Eloquent;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;

/**
 * Mutation manager : convert model to mutation
 *
 * @see Manager
 */
class MutationManager extends Manager {
	/**
	 * Convert a single ObjectType to mutation
	 *
	 * @param  ObjectType $type
	 * @return array
	 */
	public function fromType(ObjectType $type) {
		$model = $type->config['model'];

		return [
			'resolve' => $this->getResolver($model),
			'args'    => $this->getColumns($model),
			'type'    => $type
		];
	}

	/**
	 * Return arguments
	 *
	 * @param  Model $model
	 * @param  array $include
	 * @return array
	 */
	protected function getColumns(Model $model, array $include = []) {
		$key = 'mutation:columns:' . get_class($model);

		if (empty($this->cache[$key])) {
			$data     = [];
			$columns  = parent::getColumns($model, $include);
			$fillable = array_flip($model->getFillable());
			$hidden   = array_flip($model->getHidden());
			$primary  = $model->getKeyName();

			if (!empty($fillable)) {
				$columns = array_intersect_key($columns, $fillable);
			}

			if (!empty($hidden)) {
				$columns = array_diff_key($columns, $hidden);
			}

			if (!array_key_exists($primary, $columns)) {
				$data[$primary] = GraphQLType::id();
			}

			// Parse each column in order to know which is fillable. To allow
			// model to be updated, we have to use a uniq id : the id
			foreach ($columns as $column => $type) {
				$data[$column] = ['type' => $type];
			}

			$this->cache[$key] = $data;
		}

		return $this->cache[$key];
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
