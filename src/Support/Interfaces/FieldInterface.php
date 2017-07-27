<?php
namespace StudioNet\GraphQL\Support\Interfaces;

/**
 * FieldInterface
 *
 * @interface
 * @see BaseFieldInterface
 */
interface FieldInterface extends BaseFieldInterface {
	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments();

	/**
	 * Return instance name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Return user defined attributes
	 *
	 * @return array
	 */
	public function getAttributes();
}
