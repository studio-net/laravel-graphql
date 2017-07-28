<?php
namespace StudioNet\GraphQL\Support;

abstract class Field {
	/**
	 * Return availabled attributes
	 *
	 * @return array
	 */
	public function getAttributes() {
		return [];
	}

	/**
	 * Return availabled arguments
	 *
	 * @return array
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
