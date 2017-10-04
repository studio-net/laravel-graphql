<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Tests\Entity\User;

/**
 * Specify user GraphQL definition
 *
 * @see Definition
 */
class UserDefinition extends Definition {
	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getName() {
		return 'User';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a User';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function getSource() {
		return User::class;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id'          => Type::id(),
			'name'        => Type::string(),
			'last_login'  => Type::datetime(),
			'is_admin'    => Type::bool(),
			'permissions' => Type::array(),
			'posts'       => \GraphQL::listOf('post')
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getMutable() {
		return [
			'id'          => Type::id(),
			'name'        => Type::string(),
			'is_admin'    => Type::bool(),
			'permissions' => Type::array(),
			'password'    => Type::string()
		];
	}
}
