<?php
namespace StudioNet\GraphQL\Generator\Query;

use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\Generator\EloquentGenerator;
use StudioNet\GraphQL\Generator\Generator;
use StudioNet\GraphQL\Type\EloquentObjectType;

/**
 * Generate singular query from Eloquent object type
 *
 * @see EloquentGenerator
 */
class NodeEloquentGenerator extends EloquentGenerator {
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
			'args'    => $this->getArguments(),
			'type'    => $instance,
			'resolve' => $this->getResolver($instance->getModel())
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getArguments() {
		return [
			'id' => ['type' => GraphQLType::nonNull(GraphQLType::id()), 'description' => 'Primary key lookup']
		];
	}
}
