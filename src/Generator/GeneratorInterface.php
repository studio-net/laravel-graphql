<?php
namespace StudioNet\GraphQL\Generator;

/**
 * GeneratorInterface
 */
interface GeneratorInterface {
	/**
	 * Check if the given generator can handle given instance
	 *
	 * @param  mixed $instance
	 * @return bool
	 */
	public function supports($instance);

	/**
	 * Generate a field
	 *
	 * @param  mixed $instance
	 * @return array
	 */
	public function generate($instance);

	/**
	 * Return field key
	 *
	 * @param  mixed $instance
	 * @return string
	 */
	public function getKey($instance);

	/**
	 * Assert a specific type is registered before executing. If not, the
	 * generator will not be triggered
	 *
	 * @return array
	 */
	public function dependsOn();
}
