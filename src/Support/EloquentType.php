<?php
namespace StudioNet\GraphQL\Support;

/**
 * Type
 *
 * @see TypeInterface
 * @abstract
 */
abstract class EloquentType extends Type implements Interfaces\EloquentTypeInterface {
	/**
	 * {@inheritDoc}
	 */
	public function resolve($root, array $context) {
		$entity  = $this->getEntityClass();
		$primary = (new $entity)->getKeyName();
		$builder = $entity::query();

		// Retrieve single node
		if (array_key_exists('id', $context)) {
			return $builder->findOrFail($context['id']);
		}

		foreach ($context as $key => $value) {
			switch ($key) {
				case 'after'  : $builder->where($primary, '>', $value) ; break;
				case 'before' : $builder->where($primary, '<', $value) ; break;
				case 'skip'   : $builder->skip($value)                 ; break;
				case 'take'   : $builder->take($value)                 ; break;
			}
		}

		return $builder->get();
	}
}
