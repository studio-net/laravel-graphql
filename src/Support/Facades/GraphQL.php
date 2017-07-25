<?php
namespace StudioNet\GraphQL\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * GraphQL
 *
 * @see Facade
 */
class GraphQL extends Facade {
	/**
	 * {@inheritDoc}
	 */
	protected static function getFacadeAccessor() {
		return 'graphql';
	}
}
