<?php
namespace StudioNet\GraphQL\Support\Scalar;

use Carbon\Carbon;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\ScalarType;

class Timestamp extends ScalarType {
	/** @var string $name */
	public $name = "Timestamp";

	/** @var string $description */
	public $description = "A UNIX timestamp represented as an integer";

	/**
	 * Serializes an internal value to include in a response.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function serialize($value) {
		return $this->toTimestamp($value);
	}

	/**
	 * Parses an externally provided value (query variable) to use as an input
	 *
	 * @param  string $value
	 * @return integer
	 */
	public function parseValue($value) {
		return $this->toTimestamp($value);
	}

	/**
	 * Parses an externally provided literal value (hardcoded in GraphQL query)
	 * to use as an input
	 *
	 * @param \GraphQL\Language\AST\Node $valueNode
	 * @return Carbon|null
	 */
	public function parseLiteral($ast) {
		if ($ast instanceof IntValueNode) {
			$val = (int) $ast->value;

			if ($ast->value === (string) $val && self::MIN_INT <= $val && $val <= self::MAX_INT) {
				return Carbon::createFromTimestamp($val);
			}
		}

		return null;
	}

	/**
	 * Turn a value into a timestamp
	 *
	 * @param  string $value
	 * @return int
	 */
	protected function toTimestamp($value) {
		return (new Carbon($value))->getTimestamp();
	}
}
