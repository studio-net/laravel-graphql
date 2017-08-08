<?php
namespace StudioNet\GraphQL\Support\Type;

use StudioNet\GraphQL\Support\Type;
use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * Meta
 *
 * @see Type
 */
class Meta extends Type {
	/**
	 * {@inheritDoc}
	 */
	public function getName() {
		return 'Meta';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDescription() {
		return 'Base-type metadata';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFields() {
		return [
			'count' => ['type' => GraphQLType::nonNull(GraphQLType::int())],
		];
	}
}
