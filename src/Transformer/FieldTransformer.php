<?php
namespace StudioNet\GraphQL\Transformer;

use StudioNet\GraphQL\Support\FieldInterface;

/**
 * FieldTransformer
 *
 * @see Transformer
 */
class FieldTransformer extends Transformer {
	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof FieldInterface);
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform($instance) {
		$attributes = $instance->getAttributes() + [
			'type' => $instance->getRelatedType(),
			'args' => $instance->getArguments()
		];

		if (method_exists($instance, 'getResolver')) {
			$attributes = $attributes + [
				'resolve' => [$this, 'resolve']
			];
		}

		return $attributes;
	}
}
