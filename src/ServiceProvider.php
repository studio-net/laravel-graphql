<?php
namespace StudioNet\GraphQL;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider {
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {
		$config = $this->getConfigurationPath() . '/' . 'config.php';
		$routes = $this->getConfigurationPath() . '/' . 'routes.php';

		$this->mergeConfigFrom($config, 'graphql');
		$this->loadRoutesFrom($routes);
		$this->publishes([$config => config_path('graphql.php')]);

		// Call external methods to load defined schemas and others things
		$this->registerSchemas();
		$this->registerTypes();
	}

	/**
	 * Register schemas
	 *
	 * @return void
	 */
	public function registerSchemas() {
		$schemas = config('graphql.schemas', []);

		foreach ($schemas as $name => $data) {
			$this->app['graphql']->registerSchema($name, $data);
		}
	}

	/**
	 * Register types
	 *
	 * @return void
	 */
	public function registerTypes() {
		$types = config('graphql.types', []);

		foreach ($types as $type) {
			$this->app['graphql']->registerType($type);
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton(GraphQL::class, function($app) { return new GraphQL($app); });
		$this->app->bind('graphql', GraphQL::class);
	}

	/**
	 * Get the services provided by the provider
	 *
	 * @return array
	 */
	public function provides() {
		return ['graphql', GraphQL::class];
	}

	/**
	 * Return configuration file path
	 *
	 * @return string
	 */
	private function getConfigurationPath() {
		return __DIR__ . '/' . '..';
	}
}
