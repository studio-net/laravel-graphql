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
		$key  = 'schema:' . get_class($model);
		$data = [];

		if (empty($this->cache[$key])) {
			if (!empty($model->getFillable())) {
				$columns = $model->getFillable();
			} else {
				$columns = parent::getColumns($model, $include);
			}

			$pKey = $model->getKeyName();

			if (!array_key_exists($pKey, $columns)) {
				$columns[] = $pKey;
			}

			// Parse each column in order to know which is fillable. To allow
			// model to be updated, we have to use a uniq id : the id
			foreach ($columns as $column) {
				switch ($column) {
					case $pKey : $type = GraphQLType::nonNull(GraphQLType::id()) ; break;
					default    : $type = GraphQLType::string()                   ; break;
				}

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
			$pKey = $model->getKeyName();
			$data = $model->query()->findOrFail($args[$pKey]);
			$data->update($args);
		
			return $data;
		};
	}
}
