<?php
namespace StudioNet\GraphQL\Support\Interfaces;

/**
 * TypeInterface
 *
 * @interface
 */
interface FieldInterface {
	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments();

	/**
	 * Resolve current type
	 *
	 * @param  mixed $root
	 * @param  array $context
	 * @return Illuminate\Database\Eloquent\Collection|array
	 */
	public function resolve($root, array $context);

	/**
	 * Return user defined attributes
	 *
	 * @return array
	 */
	public function getAttributes();

	/**
	 * Return built attributes
	 *
	 * @return array
	 */
	public function getBuiltAttributes();

	/**
	 * Convert instance to GraphQL\Type\Definition\Type
	 *
	 * @return GraphQL\Type\Definition\Type
	 */
	public function toType();
}
