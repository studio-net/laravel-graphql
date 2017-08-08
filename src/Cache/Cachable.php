<?php
namespace StudioNet\GraphQL\Cache;

use Cache\Namespaced\NamespacedCachePool;

/**
 * Cachable
 *
 * @see CachableInterface
 * @abstract
 */
abstract class Cachable implements CachableInterface {
	/** @var NamespacedCachePool $cache */
	protected $cache;

	/**
	 * __construct
	 *
	 * @param  CachePool $cache
	 * @return void
	 */
	public function __construct(CachePool $cache) {
		$this->cache = new NamespacedCachePool($cache, $this->getCacheNamespace());
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCacheNamespace() {
		return md5(get_called_class());
	}

	/**
	 * Save data into the cache
	 *
	 * @param  string $key
	 * @param  mixed $data
	 * @return bool
	 */
	public function save($key, $data) {
		$item = $this->cache->getItem($key);
		$item->set($data);
	
		return $this->cache->save($item);
	}

	/**
	 * Check if the cache contains given key
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function has($key) {
		return $this->cache->hasItem($key);
	}

	/**
	 * Return cache content
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key) {
		return $this->cache->getItem($key)->get();
	}
}
