<?php
namespace StudioNet\GraphQL\Transformer\Type;

use Illuminate\Database\Eloquent\Model;
use StudioNet\GraphQL\Type\EloquentObjectType;
use StudioNet\GraphQL\Support\Interfaces\ModelAttributes;
use StudioNet\GraphQL\Transformer\Transformer;

/**
 * Convert a Model instance to an EloquentObjectType
 *
 * @see Transformer
 */
class ModelTransformer extends Transformer {
	/**
	 * {@inheritDoc}
	 */
	public function supports($instance) {
		return ($instance instanceof Model);
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform($instance) {
		return new EloquentObjectType([
			'name'        => $this->getName($instance),
			'description' => $this->getDescription($instance),
			'fields'      => $this->getFields($instance)
		]);
	}

	/**
	 * Return name of given model
	 *
	 * @param  Model $model
	 * @return string
	 */
	private function getName(Model $model) {
		if ($model instanceof ModelAttributes) {
			return $model->getObjectName();
		}
	
		return ucfirst(with(new \ReflectionClass($model))->getShortName());
	}

	/**
	 * Return model description
	 *
	 * @param  Model $model
	 * @return string
	 */
	private function getDescription(Model $model) {
		if ($model instanceof ModelAttributes) {
			return $model->getObjectDescription();
		}
	
		return sprintf('A %s model representation', $this->getName($model));
	}

	/**
	 * TODO
	 *
	 * Return corresponding fields. We're prefer using callable here because of
	 * recursive models. As this method handles relationships, we have to manage
	 * all depths cases
	 *
	 * @param  Model $model
	 * @return callable
	 */
	private function getFields(Model $model) {
		return [];
	}
}
