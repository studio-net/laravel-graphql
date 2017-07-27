<?php
namespace StudioNet\GraphQL\Support\Interfaces;

/**
 * EloquentTypeInterface
 *
 * @interface
 */
interface EloquentTypeInterface extends TypeInterface {
	/**
	 * Return an entity class name
	 *
	 * @return string
	 */
	public function getEntityClass();
}
