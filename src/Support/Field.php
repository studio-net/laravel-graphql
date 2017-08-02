<?php
namespace StudioNet\GraphQL\Support;

/**
 * Represent a field
 *
 * @see FieldInterface
 * @abstract
 */
abstract class Field implements FieldInterface {
	/**
	 * {@inheritDoc}
	 */
	public function getAttributes() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getArguments() {
		return [];
	}
}
