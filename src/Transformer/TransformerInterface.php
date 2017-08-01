<?php
namespace StudioNet\GraphQL\Transformer;

interface TransformerInterface {
	/**
	 * Check if the current transformer can handle given instance
	 *
	 * @param  mixed $instance
	 * @return bool
	 */
	public function supports($instance);

	/**
	 * Transform given instance
	 *
	 * @param  mixed $instance
	 * @return mixed
	 */
	public function transform($instance);
}
