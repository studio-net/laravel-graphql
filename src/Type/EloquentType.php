<?php
namespace StudioNet\GraphQL\Type;

use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * Type
 *
 * @see TypeInterface
 * @abstract
 */
abstract class EloquentType extends Type implements EloquentTypeInterface {
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

	/**
	 * {@inheritDoc}
	 */
	public function getArguments() {
		return [
			'id'     => ['type' => GraphQLType::id()  , 'description' => 'Retrieve single entity'  ] ,
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}
}
