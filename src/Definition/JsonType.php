<?php
namespace StudioNet\GraphQL\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\ObjectValueNode;

/**
 * Represent an array
 *
 * @see ScalarType
 */
class JsonType extends ScalarType {
	/** @var string $name */
	public $name = Type::JSON;

	/** @var string $description */
	public $description = 'The `json` scalar type represents a JS object';

	/**
	 * {@inheritDoc}
	 *
	 * @param  array $value
	 * @return array
	 */
	public function serialize($value) {
		return (array) $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  mixed $value
	 * @return array|null
	 */
	public function parseValue($value) {
		return (is_array($value)) ? $value : null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  GraphQL\Language\AST\Node $ast
	 * @return array|null
	 */
	public function parseLiteral($ast) {
		if ($ast instanceof ObjectValueNode) {
			return (array) $ast->value;
		}

		return null;
	}
}
