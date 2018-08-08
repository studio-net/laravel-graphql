<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Tests\Entity\Country;
use StudioNet\GraphQL\Filter\EqualsOrContainsFilter;

/**
 * Specify user GraphQL definition
 *
 * @see EloquentDefinition
 */
class CommentDefinition extends EloquentDefinition {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getName() {
		return 'Comment';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a Comment';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getSource() {
		return Country::class;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id' => Type::id(),
			'body' => Type::string(),
//			'commentable' => \GraphQL::listOf('post')
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getMutable() {
		return [
			'id' => Type::id(),
			'body' => Type::string(),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFilterable() {
		return [
			'id' => new EqualsOrContainsFilter()
		];
	}

}
