<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Tests\Entity\Tag;

/**
 * Specify tag GraphQL definition
 *
 * @see EloquentDefinition
 */
class TagDefinition extends EloquentDefinition {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getName() {
		return 'Tag';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a Tag';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getSource() {
		return Tag::class;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id'    => Type::id(),
			'name'  => Type::string(),
			'posts' => \GraphQL::listOf('post'),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getMutable() {
		return [
			'name' => Type::string(),
		];
	}
}
