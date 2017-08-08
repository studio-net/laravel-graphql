<?php
namespace StudioNet\GraphQL\Generator;

use Illuminate\Foundation\Application;

/**
 * Generator
 *
 * @see GeneratorInterface
 */
abstract class Generator implements GeneratorInterface {
	/** @var Application $app */
	protected $app;

	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @return void
	 */
	public function __construct(Application $app) {
		$this->app = $app;
	}

	/**
	 * {@inheritDoc}
	 */
	public function dependsOn() {
		return [];
	}
}
