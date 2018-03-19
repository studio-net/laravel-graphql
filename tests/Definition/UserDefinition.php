<?php
namespace StudioNet\GraphQL\Tests\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Tests\Entity\User;
use StudioNet\GraphQL\Filter\EqualsOrContainsFilter;

/**
 * Specify user GraphQL definition
 *
 * @see EloquentDefinition
 */
class UserDefinition extends EloquentDefinition {
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
			'id' => Type::id(),
			'name' => Type::string(),
			'last_login' => Type::datetime(),
			'is_admin' => Type::bool(),
			'permissions' => Type::json(),
			'posts' => \GraphQL::listOf('post')
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
			'is_admin' => Type::bool(),
			'permissions' => Type::json(),
			'password' => Type::string(),
			'posts' => Type::listOf(\GraphQL::input('post'))
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getFilterable() {
		return [
			'id'       => new EqualsOrContainsFilter(),
			"nameLike" => function($builder, $value) {
				return $builder->whereRaw('name like ?', $value);
			},
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getRules() {
		return [
			'name' => 'between:3,10'
		];
	}
}
