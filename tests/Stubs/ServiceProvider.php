<?php
namespace StudioNet\GraphQL\Tests\Stubs;

use StudioNet\GraphQL\ServiceProvider as BaseServiceProvider;

/**
 * ServiceProvider
 *
 * @see BaseServiceProvider
 */
class ServiceProvider extends BaseServiceProvider {
	/**
	 * boot
	 *
	 * @return void
	 */
	public function boot() {
		parent::boot();

		// This method was implemented in Laravel 5.4
		if ($this->app->version() >= '5.4') {
			$this->loadMigrationsFrom(realpath(__DIR__ . '/' . '../../database/migrations'));
		}
	}
}
