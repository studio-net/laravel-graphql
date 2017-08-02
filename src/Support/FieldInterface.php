<?php
namespace StudioNet\GraphQL\Support;

/**
 * Represents must-used methods for an existing field
 */
interface FieldInterface {
	/**
	 * Return represented type
	 *
	 * @return Type
	 */
	public function getRelatedType();

	/**
	 * Return availabled attributes
	 *
	 * @return array
	 */
	public function getAttributes();

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments();
}
