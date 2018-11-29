<?php
namespace StudioNet\GraphQL\Definition;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
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
		return new Carbon($value);
	}

	/**
	 * {@inheritDoc}
	 */
	public function parseLiteral($valueNode, ?array $variables = null) {
		if ($valueNode instanceof StringValueNode) {
			return new Carbon($valueNode->value);
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
		return (new Carbon($value))->toRfc3339String();
	}
}
