<?php
namespace StudioNet\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * This class allows us to build dynamically queries and mutations based on
 * given types
 */
class TypesManager {
	/**
	 * Convert many types to query
	 *
	 * @param  array $types
	 * @return array
	 */
	static public function toQuery(array $types) {
		$query = [];

		foreach ($types as $type) {
			foreach ([false, true] as $pluralize) {
				$name  = $type->getName();
				$attrs = [
					'type'    => self::toType($type, $pluralize),
					'args'    => array_merge($type->getArguments(), self::getDefaultQueryArguments($pluralize)),
					'resolve' => [$type, 'resolve']
				];

				if ($pluralize) {
					$name = str_plural($name);
					$attrs['type'] = GraphQLType::listOf($attrs['type']);
				}
				
				$query[$name] = $attrs;
			}
		}

		return $query;
	}

	/**
	 * Convert many types to mutation
	 *
	 * @param  array $types
	 * @return arrray
	 */
	static public function toMutation(array $types) {
		return [];
	}

	/**
	 * Return default arguments for query : $pluralize variable can etablish
	 * links between useful arguments like `id` or `after, before, ...`
	 *
	 * @param  bool $pluralize
	 * @return array
	 */
	static public function getDefaultQueryArguments($pluralize = false) {
		if ($pluralize === false) {
			return [
				'id' => ['type' => GraphQLType::id()  , 'description' => 'Retrieve single entity']
			];
		}

		return [
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}

	/**
	 * Convert TypeInterface to ObjectType
	 *
	 * We cannot call TypeInterface::toType() here because we need override some
	 * values if the pluralize variable is set to true
	 *
	 * @param  TypeInterface $type
	 * @param  bool $pluralize
	 *
	 * @return ObjectType
	 */
	static public function toType(TypeInterface $type, $pluralize = false) {
		if ($pluralize === false) {
			return $type->toType();
		}

		$attrs = $type->toArray();
		$attrs = array_merge($attrs, [
			'name' => str_plural($attrs['name'])
		]);

		return new ObjectType($attrs);
	}
}
