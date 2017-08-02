<?php
namespace StudioNet\GraphQL\Support;

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
