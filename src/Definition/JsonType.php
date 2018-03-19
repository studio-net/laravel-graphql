<?php
namespace StudioNet\GraphQL\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST;

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
		return $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  mixed $value
	 * @return array|null
	 */
	public function parseValue($value) {
		return $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  GraphQL\Language\AST $valueNode
	 * @return array|null
	 */
	public function parseLiteral($valueNode) {
		switch ($valueNode) {
		case ($valueNode instanceof AST\StringValueNode):
		case ($valueNode instanceof AST\BooleanValueNode): return $valueNode->value;
		case ($valueNode instanceof AST\IntValueNode):
		case ($valueNode instanceof AST\FloatValueNode): return floatval($valueNode->value);
		case ($valueNode instanceof AST\ListValueNode): return array_map([$this, 'parseLiteral'], $valueNode->values);
		case ($valueNode instanceof AST\ObjectValueNode): {
			$value = [];

			foreach ($valueNode->fields as $field) {
				$value[$field->name->value] = $this->parseLiteral($field->value);
			}

			return $value;
		}
		}

		return null;
	}
}
