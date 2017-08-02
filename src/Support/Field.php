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

	/**
	 * Convert current field into array
	 *
	 * @return array
	 */
	public function toArray() {
		$attributes = $this->getAttributes() + [
			'type' => $this->getRelatedType(),
			'args' => $this->getArguments()
		];

		// Assert we have a resolve method
		if (method_exists($this, 'resolve')) {
			$attributes = $attributes + [
				'resolve' => [$this, 'resolve']
			];
		}

		return $attributes;
	}
}
