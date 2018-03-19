<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Tests\Entity\Post;

/**
 * Specify post GraphQL definition
 *
 * @see EloquentDefinition
 */
class PostDefinition extends EloquentDefinition {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getName() {
		return 'Post';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a Post';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getSource() {
		return Post::class;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id' => Type::id(),
			'title' => Type::string(),
			'content' => Type::string(),
			'tags' => \GraphQL::listOf('tag'),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getMutable() {
		return [
			'title' => Type::string(),
			'content' => Type::string(),
			'tags' => Type::listOf(\GraphQL::input('tag'))
		];
	}
}
