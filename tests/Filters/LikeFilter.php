<?php

namespace StudioNet\GraphQL\Tests\Filters;

use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Filter\TypedFilterInterface;

class LikeFilter implements TypedFilterInterface {
	private $column;

	public function __construct($column = null) {
		$this->column = $column;
	}

	public function getType() {
		return Type::string();
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateBuilder(Builder $builder, $value, $key) {
		if (!empty($this->column)) {
			$key = $this->column;
		}

		return $builder->orWhere(
			$key,
			'LIKE',
			"%{$value}%"
		);
	}
}
