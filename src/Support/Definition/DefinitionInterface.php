<?php
namespace StudioNet\GraphQL\Support\Definition;

/**
 * Definition interface
 */
interface DefinitionInterface {
	/**
	 * Return the GraphQL\Type\Definition\ObjectType name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Return the GraphQL\Type\Definition\ObjectType description
	 *
	 * @return string
	 */
	public function getDescription();

	/**
	 * Return object source
	 *
	 * @return string
	 */
	public function getSource();
}
