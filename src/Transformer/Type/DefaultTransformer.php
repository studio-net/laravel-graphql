<?php
namespace StudioNet\GraphQL\Transformer\Type;

use StudioNet\GraphQL\Support\TypeInterface;
use GraphQL\Type\Definition\ObjectType;

/**
 * Convert a TypeInterface to ObjectType
 */
class DefaultTransformer {
	/**
	 * supports
	 *
	 * @param  mixed $instance
	 * @return bool
	 */
	public function supports($instance) {
		return ($instance instanceof TypeInterface);
	}

	/**
	 * Transform a Model to an EloquentObjectType
	 *
	 * @param  Model $model
	 * @return EloquentObjectType
	 */
	public function transform(TypeInterface $type) {
		$fields     = $type->getFields();
		$attributes = $type->getAttributes();
		$interfaces = $type->getInterfaces();

		foreach ($fields as $key => $field) {
			if (is_array($field)) {
				$resolver = $this->getFieldResolver($type, $key, $field);

				if ($resolver !== null) {
					$fields[$key]['resolve'] = $resolver;
				}
			}
		}

		$attributes = array_merge($attributes, [
			'fields' => $fields,
			'name'   => $this->getName(),
			'description' => $this->getDescription()
		]);

		if (!empty($nterfaces)) {
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
