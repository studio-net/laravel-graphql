<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Filter\EqualsOrContainsFilter;
use StudioNet\GraphQL\Tests\Entity\Label;

/**
 * Specify user GraphQL definition
 *
 * @see EloquentDefinition
 */
class LabelDefinition extends EloquentDefinition {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getName() {
		return 'Label';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a Label';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getSource() {
		return Label::class;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id' => Type::id(),
			'name' => Type::string(),
			'posts' => \GraphQL::listOf('post'),
			'users' => \GraphQL::listOf('user'),
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
			'name' => Type::string(),
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
