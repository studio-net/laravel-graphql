<?php
namespace StudioNet\GraphQL\Support\Interfaces;

/**
 * BaseFieldInterface
 *
 * @interface
 */
interface BaseFieldInterface {
	/**
	 * Resolve current type
	 *
	 * @param  mixed $root
	 * @param  array $context
	 * @return Illuminate\Database\Eloquent\Collection|array
	 */
	public function resolve($root, array $context);

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
