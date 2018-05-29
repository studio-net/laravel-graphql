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

	/** PAGINATION */
	const PAGINATION = 'Pagination';

	/** @var array $cache */
	protected static $cache = null;

	/**
	 * Assign a comment to scalar type without creating array
	 *
	 * @param  \GraphQL\Type\Definition\ScalarType $type
	 * @param  string $description
	 * @return array
	 */
	public static function assign(GraphQLType $type, $description) {
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
	public static function json() {
		return self::getCache(self::JSON);
	}

	/**
	 * Return datetime type
	 *
	 * @return DatetimeType
	 */
	public static function datetime() {
		return self::getCache(self::DATETIME);
	}

	/**
	 * Return pagination type
	 *
	 * @return PaginationType
	 */
	public static function pagination() {
		return self::getCache(self::PAGINATION);
	}

	/**
	 * Alias of `boolean`
	 *
	 * @return \GraphQL\Type\Definition\BooleanType
	 */
	public static function bool() {
		return self::boolean();
	}

	/**
	 * Return cached element
	 *
	 * @param  string $name
	 * @return mixed
	 */
	protected static function getCache($name = null) {
		if (self::$cache === null) {
			self::$cache = [
				self::JSON => new JsonType,
				self::DATETIME => new DatetimeType,
				self::PAGINATION => new PaginationType,
			];
		}

		return ($name) ? self::$cache[$name] : self::$cache;
	}
}
