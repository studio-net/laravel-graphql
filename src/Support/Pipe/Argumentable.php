<?php
namespace StudioNet\GraphQL\Support\Pipe;

use StudioNet\GraphQL\Support\Definition\Definition;

/**
 * Argumentable
 */
interface Argumentable {
	/**
	 * Defines arguments list
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition): array;
}
