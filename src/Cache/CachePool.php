<?php
namespace StudioNet\GraphQL\Cache;

use Cache\Adapter\PHPArray\ArrayCachePool;

/**
 * Define global cache system in order to prevent retrieve twice informations
 *
 * @see ArrayCachePool
 */
class CachePool extends ArrayCachePool {
}
