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
		$this->cache = $cache;
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
	 * @param  mixed  $data
	 * @return bool
	 */
	public function save($key, $data) {
		$namespace = $this->getCacheNamespace();
		$item      = $this->cache->getItem(strtolower($namespace));
		$content   = (is_null($item->get())) ? [] : $item->get();
		$content   = $content + [strtolower($key) => $data];
		$item->set($content);

		return $this->cache->save($item);
	}

	/**
	 * Push element in array cache
	 *
	 * @param  string $key
	 * @param  mixed $data
	 * @return bool
	 */
	public function push($namespace, $key, $data) {
		$namespace = $this->getCacheNamespace();
		$item      = $this->cache->getItem(strtolower($namespace));
		$content   = (is_null($item->get())) ? [] : $item->get();

		if (!array_key_exists($key, $content)) {
			$content[$key] = [];
		}

		array_push($content[$key], $data);
		$item->set($content);

		return $this->cache->save($item);
	}

	/**
	 * Check if cache has key within the namespace
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function has($key) {
		return array_key_exists($key, $this->get());
	}

	/**
	 * Return cache content
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key = null) {
		$namespace = $this->getCacheNamespace();
		$data      = $this->cache->getItem(strtolower($namespace))->get();
		$data      = empty($data) ? [] : $data;

		return (is_null($key)) ? $data : $data[$key];
	}
}
