<?php
namespace StudioNet\GraphQL\Support;

/**
 * Represent a class implementation of an ObjectType
 *
 * @see TypeInterface
 */
abstract class Type implements TypeInterface {
	/**
	 * {@inheritDoc}
	 */
	public function getAttributes() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFields() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInterfaces() {
		return [];
	}
}
