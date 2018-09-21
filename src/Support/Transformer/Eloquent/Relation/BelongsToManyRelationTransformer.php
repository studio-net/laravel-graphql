<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Relations;

class BelongsToManyRelationTransformer extends AbstractRelationTransformer {

	/**
	 * Store values in entity.
	 */
	protected function hydrate() {
		if (!is_array(array_first($this->values))) {
			$this->values = [$this->values];
		}

		$toKeep = array_map(function ($value) {
			return array_get($value, 'id', null);
		}, $this->values);

		$this->resetRelation();

		$this->values = array_filter($toKeep, function ($value) {
			return !is_null($value);
		});
	}

	/**
	 * Override.
	 */
	public function afterSave() {
		$this->relation->sync($this->values);
	}
}
