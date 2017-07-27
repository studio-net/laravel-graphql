<?php
namespace StudioNet\GraphQL\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Query
 *
 * @see Field
 */
class Query extends Field {
	/**
	 * Build query from entity
	 *
	 * @param  Model $model
	 * @return self
	 */
	static public function fromEntity(Model $model) {
		
	}
}
