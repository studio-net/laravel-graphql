<?php
namespace StudioNet\GraphQL\Generator\Query;

use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\Generator\EloquentGenerator;
use StudioNet\GraphQL\Generator\Generator;
use StudioNet\GraphQL\Type\EloquentObjectType;

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
			'args'    => $this->getArguments(),
			'type'    => GraphQLType::listOf($instance),
			'resolve' => $this->getResolver($instance->getModel())
		];
	}

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments() {
		return [
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}
}
