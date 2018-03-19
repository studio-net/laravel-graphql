<?php

namespace StudioNet\GraphQL\Filter;

use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Exception\FilterException;

/**
 * Simple filter which check if field "is equal" or "contains"
 */
class EqualsOrContainsFilter implements FilterInterface {

	/**
	 * {@inheritDoc}
	 */
	public function updateBuilder(Builder $builder, $value, $key) {
		$whereFunc = 'where';

		if (is_array($value)) {
			if (empty($value)) {
				throw new FilterException("Invalid filter (empty array for $key)");
			}
			if (count($value) === 1) {
				$value = head($value);
			} else {
				$whereFunc .= "In";
			}
		}

		$builder->$whereFunc($key, $value);
	}
}
