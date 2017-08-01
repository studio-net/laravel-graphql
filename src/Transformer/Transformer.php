<?php
namespace StudioNet\GraphQL\Transformer;

use Illuminate\Foundation\Application;

/**
 * Base transformer class
 *
 * @see TransformerInterface
 * @abstract
 */
abstract class Transformer implements TransformerInterface {
	/** @var Application $app */
	private $app;

	/**
	 * __construct
	 *
	 * @param  Application $application
	 * @return void
	 */
	public function __construct(Application $application) {
		$this->app = $application;
	}
}
