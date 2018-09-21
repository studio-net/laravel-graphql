<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Relations;

class MorphToRelationTransformer extends AbstractRelationTransformer {

		/**
	 * Store values in entity.
	 */
	protected function hydrate() {
		$id = array_get($this->values, 'id', null);
		$type = array_get($this->values, '__typename', null);

		if (is_null($type)) {
			throw new \Exception(
				"Can't update polymorphic relation without specify type"
			);
		}

		// TODO: maybe there is a smarter way to guess type
		$className = '\App\\' . $type;
		if (!class_exists($className)) {
			throw new \Exception("Unknown $className type");
		}

		$this->entity = $className::findOrNew($id);
		$this->entity->fill($this->values)->save();
	}

	/**
	 * Associate created/updated entity.
	 */
	protected function associate() {
		$this->relation->associate($this->entity);
	}
}
