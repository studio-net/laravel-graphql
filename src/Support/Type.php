<?php
namespace StudioNet\GraphQL\Support;

use GraphQL\Type\Definition\ObjectType;

class Type {
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

	/**
	 * Return a field resolver : you can define method like
	 * `resolve{Field}Field` in order to resolve some field dynamically
	 *
	 * @param  string $name
	 * @param  array $field
	 *
	 * @return callable|null
	 */
	protected function getFieldResolver($name, array $field) {
		if (array_key_exists('resolve', $field)) {
			return $field['resove'];
		}

		$method = studly_case(sprintf('resolve-%s-field', $name));

		if (method_exists($this, $method)) {
			return [$this, $method];
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray() {
		$fields     = $this->getFields();
		$attributes = $this->getAttributes();
		$interfaces = $this->getInterfaces();

		foreach ($fields as $key => $field) {
			if (is_array($field) and method_exists($this, 'getFieldResolver')) {
				$resolver = $this->getFieldResolver($key, $field);

				if ($resolver !== null) {
					$fields[$key]['resolve'] = $resolver;
				}
			}
		}

		$attributes = array_merge($attributes, ['fields' => $fields]);

		if (!empty($interfaces)) {
			$attributes['interfaces'] = $interfaces;
		}

		return $attributes;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toType() {
		return new ObjectType($this->toArray());
	}
}
