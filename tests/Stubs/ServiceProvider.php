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
		$this->loadMigrationsFrom(realpath(__DIR__ . '/' . '../../database/migrations'));
	}
}
