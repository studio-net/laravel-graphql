<?php
namespace StudioNet\GraphQL\Support\Definition;

use GraphQL\Type\Definition\ObjectType;

/**
 * Represent a field
 *
 * @see FieldInterface
 * @abstract
 */
abstract class Field implements FieldInterface {
	/**
	 * Return field name
	 *
	 * @return string
	 */
	public function getName() {
		return array_last(explode('\\', strtolower(get_called_class())));
	}

	/**
	 * Return field description
	 *
	 * @return string
	 */
	public function getDescription() {
		return '';
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
	public function getArguments() {
		return [];
	}

	/**
	 * Resolve as GraphQL\Type\Definition\ObjectType
	 *
	 * @return ObjectType
	 */
	public function resolveType() {
		$attributes = $this->getAttributes() + [
			'type'        => $this->getRelatedType(),
			'args'        => $this->getArguments(),
			'description' => $this->getDescription()
		];

		// Append resolver if exists
		if (method_exists($this, 'getResolver')) {
			$attributes['resolve'] = [$this, 'getResolver'];
		}

		return $attributes;
	}
}
