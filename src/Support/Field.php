<?php
namespace StudioNet\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;

/**
 * Field
 *
 * @see FieldInterface
 * @abstract
 */
abstract class Field implements Interfaces\FieldInterface {
	/**
	 * {@inheritDoc}
	 */
	public function getArguments() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttributes() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getBuiltAttributes() {
		$attributes = $this->getAttributes();
		$attributes = array_merge([
			'args'    => $this->getArguments(),
			'type'    => $this->getType()
		], $attributes);

		if (method_exists($this, 'resolve')) {
			$attributes['resolve'] = [$this, 'resolve'];
		}

		return $attributes;
	}

	/**
	 * Return instance as array
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->getBuiltAttributes();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toType() {
		return new ObjectType($this->toArray());
	}
}
