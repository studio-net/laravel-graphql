<?php
namespace StudioNet\GraphQL\Cache;

/**
 * CachebleInterface
 */
interface CachableInterface {
	/**
	 * Return cache namespace
	 *
	 * @return string
	 */
	public function getCacheNamespace();
}
