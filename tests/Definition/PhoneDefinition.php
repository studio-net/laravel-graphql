<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Tests\Entity\Phone;
use StudioNet\GraphQL\Filter\EqualsOrContainsFilter;

/**
 * Specify user GraphQL definition
 *
 * @see EloquentDefinition
 */
class PhoneDefinition extends EloquentDefinition {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getName() {
		return 'Phone';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a Phone';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getSource() {
		return Phone::class;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id' => Type::id(),
			'label' => Type::string(),
			'number' => Type::string(),
			'user' => \GraphQL::type('user')
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
			'label' => Type::string(),
			'number' => Type::string(),
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
