<?php

namespace StudioNet\GraphQL\Filter;

use GraphQL\Type\Definition\InputType;

/**
 * Abstract filter
 */
interface TypedFilterInterface extends FilterInterface {

	/**
	 * Defines GraphQL Type if current filter
	 * @return InputType $wrappedType
	 */
	public function getType();
}
