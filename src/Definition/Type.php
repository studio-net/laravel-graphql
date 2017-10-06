<?php
namespace StudioNet\GraphQL\Definition;

use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * Type
 *
 * @see GraphQLType
 * @abstract
 */
abstract class Type extends GraphQLType {
	/** ARRAY */
	const JSON = 'Json';

	/** DATETIME */
	const DATETIME = 'Datetime';

	/** @var array $cache */
	static protected $cache;

	/**
	 * Assign a comment to scalar type without creating array
	 *
	 * @param  ScalarType $type
	 * @param  string $description
	 *
	 * @return array
	 */
	static public function assign(GraphQLType $type, $description) {
		return [
			'type' => $type,
			'description' => $description
		];
	}

	/**
	 * Return json type
	 *
	 * @return JsonType
	 */
	static public function json() {
		return self::getCache(self::JSON);
	}

	/**
	 * Return datetime type
	 *
	 * @return DateTimeType
	 */
	static public function datetime() {
		return self::getCache(self::DATETIME);
	}

	/**
	 * Alias of `boolean`
	 *
	 * @return GraphQL\Type\Definition\BooleanType
	 */
	static public function bool() {
		return self::boolean();
	}

	/**
	 * Return cached element
	 *
	 * @param  string $name
	 * @return mixed
	 */
	static protected function getCache($name = null) {
		if (is_null(self::$cache)) {
			self::$cache = [
				self::JSON => new JsonType,
				self::DATETIME => new DatetimeType,
			];
		}

		return ($name) ? self::$cache[$name] : self::$cache;
	}
}
