<?php
namespace StudioNet\GraphQL\Support\Interfaces;

/**
 * Implements two methods in order to work better with EloquentObjectType
 * instance : getObjectName and getObjectDescription. Thoses two methods allowed
 * you to define a custom name and a custom description for your model
 */
interface ModelAttributes {
	/**
	 * Return object name
	 *
	 * @return string
	 */
	public function getObjectName();

	/**
	 * Return object description
	 *
	 * @return string
	 */
	public function getObjectDescription();
}
