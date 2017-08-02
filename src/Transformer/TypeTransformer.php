<?php
namespace StudioNet\GraphQL\Transformer;

use GraphQL\Type\Definition\ObjectType;
use StudioNet\GraphQL\Support\TypeInterface;
use StudioNet\GraphQL\Transformer\Transformer;

/**
 * Convert a TypeInterface to ObjectType
 *
 * @see Transformer
 */
class TypeTransformer extends Transformer {
	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof TypeInterface);
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform($instance) {
		$fields     = $instance->getFields();
		$attributes = $instance->getAttributes();
		$interfaces = $instance->getInterfaces();

		foreach ($fields as $key => $field) {
			if (is_array($field)) {
				$resolver = $this->getFieldResolver($instance, $key, $field);

				if ($resolver !== null) {
					$fields[$key]['resolve'] = $resolver;
				}
			}
		}

		// Merge all attributes within attributes var
		$attributes = array_merge($attributes, [
			'fields'      => $fields,
			'name'        => $this->getName(),
			'description' => $this->getDescription()
		]);

		if (!empty($interfaces)) {
			$attributes['interfaces'] = $interfaces;
		}

		return new ObjectType($attributes);
	}

	/**
	 * Resolve given field
	 *
	 * @param  TypeInterface $type
	 * @param  string $name
	 * @param  array $field
	 *
	 * @return callable|null
	 */
	private function getFieldResolver(TypeInterface $type, $name, array $field) {
		if (array_key_exists('resolve', $field)) {
			return $field['resolve'];
		}

		$method = studly_case(sprintf('resolve-%s-%field', $name));

		if (method_exists($type, $method)) {
			return function() use ($type, $method) { return [$type, $method]; };
		}

		return null;
	}
}
