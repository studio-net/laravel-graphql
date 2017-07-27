<?php
namespace StudioNet\GraphQL\Support\Interfaces;

/**
 * TypeInterface
 *
 * @interface
 */
interface TypeInterface extends FieldInterface {
	/**
	 * Return availabled fields
	 *
	 * @return array
	 */
	public function getFields();

	/**
	 * Return availabled interfaces
	 *
	 * @return array
	 */
	public function getInterfaces();

	/**
	 * Return description
	 *
	 * @return string
	 */
	public function getDescription();

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
}
