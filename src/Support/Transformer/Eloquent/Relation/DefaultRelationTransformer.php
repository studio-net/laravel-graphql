<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Relations;

class DefaultRelationTransformer extends AbstractRelationTransformer {

	/**
	 * Store values in entity.
	 */
	protected function hydrate() {
		if (!is_array(array_first($this->values))) {
			$this->values = [$this->values];
		}

		foreach ($this->values as $values) {
			$this->resetRelation();
			$entity = $this->relation->findOrNew(array_get($values, 'id', null));
			$fill = [];

			foreach (array_keys($values) as $key) {
				if ($entity->isFillable($key)) {
					$fill[$key] = $values[$key];
				}
			}
			$entity->fill($fill)->save();
		}
	}
}
