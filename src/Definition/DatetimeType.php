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
		$carbon = new Carbon($value);
		$carbon->timezone('UTC');
		$str = $carbon->toIso8601String();
		return $str;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  mixed $value
	 * @return array|null
	 */
	public function parseValue($value) {
		$carbon = new Carbon($value);
		$carbon->timezone(config('app.timezone'));
		return $carbon;
	}

	/**
	 * {@inheritDoc}
	 */
	public function parseLiteral($valueNode, ?array $variables = null) {
		if ($valueNode instanceof StringValueNode) {
			$carbon = new Carbon($valueNode->value);
			$carbon->timezone(config('app.timezone'));
			return $carbon;
		}
		return null;
	}

}
