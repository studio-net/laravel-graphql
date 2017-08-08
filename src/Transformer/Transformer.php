<?php
namespace StudioNet\GraphQL\Transformer;

use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Cache\Cachable;
use StudioNet\GraphQL\Cache\CachePool;

/**
 * Base transformer class
 *
 * @see TransformerInterface
 * @abstract
 */
abstract class Transformer extends Cachable implements TransformerInterface {
	/** @var Application $app */
	protected $app;

	/**
	 * __construct
	 *
	 * @param  Application $application
	 * @return void
	 */
	public function __construct(Application $application, CachePool $cache) {
		parent::__construct($cache);
		$this->app = $application;
	}
}
