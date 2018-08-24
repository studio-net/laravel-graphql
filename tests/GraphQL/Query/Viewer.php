<?php
namespace StudioNet\GraphQL\Tests\GraphQL\Query;

use StudioNet\GraphQL\Tests\Entity\User;
use StudioNet\GraphQL\Support\Definition\Query;

/**
 * Viewer query
 *
 * @see Query
 */
class Viewer extends Query {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Return the first user found in the database';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return GraphQL\Type\Definition\ObjectType
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSource() {
		return User::class;
	}

	/**
	 * Resolve query
	 *
	 * @return User
	 */
	public function getResolver($opts) {
		return User::with($opts['with'])->first();
	}
}
