<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Relations;

class HasOneRelationTransformer extends AbstractRelationTransformer {

	/**
	 * Store values in entity.
	 */
	protected function hydrate() {
		$this->entity = $this->relation->getRelated()->findOrNew(array_get($this->values, 'id', null));

		if (empty($this->entity->id)) {
			$this->entity = $this->relation->firstOrNew([]);
		}
		$this->entity->fill($this->values)->save();
	}

	/**
	 * Associate created/updated entity.
	 */
	protected function associate() {
		$this->relation->save($this->entity);
	}
}
