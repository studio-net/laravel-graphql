<?php
namespace StudioNet\GraphQL\Support\Scalar;

use GraphQL\Type\Definition\ScalarType;

class JsonArray extends ScalarType {
	/** @var string $name */
	public $name = "Array";

	/** @var string $description */
	public $description = "An array represented as JSON";

	/**
	 * Serializes an internal value to include in a response.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function serialize($value) {
		return (array) $value;
	}

	/**
	 * Parses an externally provided value (query variable) to use as an input
	 *
	 * @param  string $value
	 * @return integer
	 */
	public function parseValue($value) {
		return (array) $value;
	}

	/**
	 * Parses an externally provided literal value (hardcoded in GraphQL query)
	 * to use as an input
	 *
	 * @param \GraphQL\Language\AST\Node $valueNode
	 * @return Carbon|null
	 */
	public function parseLiteral($ast) {
		return json_decode((string) $ast->value);
	}
}
