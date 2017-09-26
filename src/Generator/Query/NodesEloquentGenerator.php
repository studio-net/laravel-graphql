<?php
namespace StudioNet\GraphQL\Generator\Query;

use GraphQL\Type\Definition\Type as GraphQLType;
use GraphQL\Type\Definition\InputObjectType as GraphQLInputObjectType;
use StudioNet\GraphQL\Generator\EloquentGenerator;
use StudioNet\GraphQL\Definition\Type\EloquentObjectType;
use Illuminate\Database\Eloquent\Model;

/**
 * Generate a pluralized query for given Eloquent object type
 *
 * @see EloquentGenerator
 */
class NodesEloquentGenerator extends EloquentGenerator {
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
		return strtolower(str_plural($instance->name));
	}

	/**
	 * {@inheritDoc}
	 */
	public function generate($instance) {
		return [
			'args'    => $this->getArguments($instance->getModel()),
			'type'    => GraphQLType::listOf($instance),
			'resolve' => $this->getResolver($instance->getModel())
		];
	}

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments(Model $model) {
		$args = [];

		// Allows user to define custom arguments for single node generation
		if (method_exists($model, 'getNodesQueryArguments')) {
			$args = $model->getNodesQueryArguments();
		}

		return $args + [
			'after'   => [
				'type' => GraphQLType::id(),
				'description' => 'Based-cursor navigation'
			],
			'before'  => [
				'type' => GraphQLType::id(),
				'description' => 'Based-cursor navigation'
			],
			'skip'    => [
				'type' => GraphQLType::int(),
				'description' => 'Offset-based navigation'
			],
			'take'    => [
				'type' => GraphQLType::int(),
				'description' => 'Limit-based navigation'
			],
			'filter'  => [
				'type' => $this->getFilterType($model),
				'description' => 'Filter for querying this entity'
			],
		];
	}

	/**
	 * Get filter type for given model.
	 *
	 * @param  Illuminate\Database\Eloquent\Model $model
	 * @return GraphQLInputObjectType
	 */
	private function getFilterType($model) {
		$table  = $model->getTable();
		$fields = [];

		// FIXME Implements column listing within the entity. Also, if not
		// provided, must take care about hidden, fillables and guarded fields
		foreach (\Schema::getColumnListing($table) as $column) {
			$fields[$column] = GraphQLType::string();
		}

		return new GraphQLInputObjectType([
			'name'   => ucfirst($table) . 'Filter',
			'fields' => $fields,
		]);
	}
}
