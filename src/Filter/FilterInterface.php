<?php

namespace StudioNet\GraphQL\Filter;

use Illuminate\Database\Eloquent\Builder;

/**
 * Abstract filter
 */
interface FilterInterface {

	/**
	 * Updates a Builder based on filter
	 *
	 * @param Builder $builder
	 * @param mixed $value
	 * @param string $key the filter key
	 * @return void
	 */
	public function updateBuilder(Builder $builder, $value, $key);

}
