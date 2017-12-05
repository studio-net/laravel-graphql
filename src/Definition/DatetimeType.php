<?php
namespace StudioNet\GraphQL\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\IntValueNode;
use Carbon\Carbon;

/**
 * Represent a datetime
 *
 * @see ScalarType
 */
class DatetimeType extends ScalarType {
	/** @var string $name */
	public $name = Type::DATETIME;

	/** @var string $description */
	public $description = 'The `datetime` scalar type represents a datetime object';

	/**
	 * {@inheritDoc}
	 *
	 * @param  array $value
	 * @return array
	 */
	public function serialize($value) {
		return $this->toTimestamp($value);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  mixed $value
	 * @return array|null
	 */
	public function parseValue($value) {
		return Carbon::createFromTimestamp($value);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  GraphQL\Language\AST\Node $ast
	 * @return Carbon|null
	 */
	public function parseLiteral($ast) {
		if ($ast instanceof IntValueNode) {
			$val = (int) $ast->value;

			if ($ast->value === (string) $val && PHP_INT_MIN <= $val && $val <= PHP_INT_MAX) {
				return Carbon::createFromTimestamp($val);
			}
		}

		return null;
	}

	/**
	 * Turn value into timestamp
	 *
	 * @param  string|int $value
	 * @return int
	 */
	private function toTimestamp($value) {
		return with(new Carbon((string) $value))->getTimestamp();
	}
}
