<?php
namespace StudioNet\GraphQL\Generator;

use Illuminate\Database\Eloquent\Model;

/**
 * Default needed methods for generate Eloquent field
 */
interface EloquentGeneratorInterface {
	/**
	 * Return resolver
	 *
	 * @param  Model $model
	 * @return callable
	 */
	public function getResolver(Model $model);

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments();
}
