<?php
namespace StudioNet\GraphQL\Type;

/**
 * TypeInterface
 *
 * @interface
 */
interface TypeInterface {
	/**
	 * Return availabled fields
	 *
	 * @return array
	 */
	public function getFields();

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments();

	/**
	 * Return availabled interfaces
	 *
	 * @return array
	 */
	public function getInterfaces();

	/**
	 * Return name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Return description
	 *
	 * @return string
	 */
	public function getDescription();

	/**
	 * Resolve current type
	 *
	 * @param  mixed $root
	 * @param  array $context
	 * @return Illuminate\Database\Eloquent\Collection|array
	 */
	public function resolve($root, array $context);

	/**
	 * Return the field resolver. It allows us to define method like
	 * `resolve{field}Field` to resolve specific field
	 *
	 * @param  string $name
	 * @param  array|GraphQL\Type\Definition\Type $field
	 *
	 * @return callable|null
	 */
	public function getFieldResolver($name, $field);

	/**
	 * Return built filters. The main goal here is to parse each field and call
	 * the `getFieldResolver` method on them
	 *
	 * @return array
	 */
	public function getBuiltFields();

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
