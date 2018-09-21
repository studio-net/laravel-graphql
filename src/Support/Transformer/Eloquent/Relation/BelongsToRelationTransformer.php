<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Relations;

class BelongsToRelationTransformer extends HasOneRelationTransformer {

	/**
	 * Associated created/updated entity.
	 */
	protected function associate() {
		$this->relation->associate($this->entity);
	}
}
