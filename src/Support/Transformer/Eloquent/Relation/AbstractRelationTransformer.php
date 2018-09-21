<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model;

class AbstractRelationTransformer {

	/**
	 * Relation linked entity to hydrate.
	 *
	 * @var Relations\Relation
	 */
	protected $relation;

	/**
	 * Values used to hydrate relation.
	 *
	 * @var array
	 */
	protected $values;

	/**
	 * Entity owner of relation to update.
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * Relation's column.
	 *
	 * @var string
	 */
	protected $column;

	/**
	 * Entity to hydrate.
	 *
	 * @var Relations\Relation
	 */
	protected $entity;

	/**
	 * Constructor.
	 */
	public function __construct(Model $model, string $column, array $values) {
		$this->model = $model;
		$this->column = $column;
		$this->values = $values;
		$this->resetRelation();
	}

	protected function resetRelation() {
		$this->relation = $this->model->{$this->column}();
	}

	/**
	 * Save values to entity linked by relation and associate it.
	 */
	public function transform() {
		$this->hydrate();
		$this->associate();
	}

	/**
	 * Store values in entity.
	 */
	protected function hydrate() {
	}

	/**
	 * Associated created/updated entity.
	 */
	protected function associate() {
	}

	/**
	 * Meant to be executed after Relation owner save.
	 */
	public function afterSAve() {
	}
}
