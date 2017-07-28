<?php
namespace StudioNet\GraphQL\Support;

interface FieldInterface {
	/**
	 * Return represented type
	 *
	 * @return Type
	 */
	public function getRelatedType();
}
