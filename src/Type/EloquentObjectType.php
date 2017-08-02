<?php
namespace StudioNet\GraphQL\Type;

use GraphQL\Utils;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ObjectType;

/**
 * Represent a Model ObjectType (just handle one more property)
 *
 * @see ObjectType
 */
class EloquentObjectType extends ObjectType {
	/** @var Model $model */
	public $model;

	/**
	 * @override
	 */
	public function __construct(array $config) {
		Utils::invariant(!empty($config['model']), 'Every eloquent is expected to have a model');

		$this->model = $config['model'];
		parent::__construct($config);
	}

	/**
	 * Return the corresponding model
	 *
	 * @return Model
	 */
	public function getModel() {
		return $this->model;
	}
}
