<?php

namespace StudioNet\GraphQL\Filter;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

/**
 * Abstract filter
 */
interface TypedFilterInterface extends FilterInterface {

	/**
	 * Defines GraphQL Type if current filter
	 * @return Type|InterfaceType|UnionType|ScalarType|InputObjectType|EnumType|ListOfType|NonNull $wrappedType
	 */
	public function getType();
}
