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
		$config = $this->getConfigurationFile();

		$this->mergeConfigFrom($config, 'graphql');
		$this->publishes([$config => config_path('graphql.php')]);

		// Call external methods to load defined schemas and others things
		$this->registerSchemas();
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
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton('graphql', function($app) {
			return new GraphQL($app);
		});
	}

	/**
	 * Get the services provided by the provider
	 *
	 * @return array
	 */
	public function provides() {
		return ['graphql'];
	}

	/**
	 * Return configuration file path
	 *
	 * @return string
	 */
	private function getConfigurationFile() {
		return __DIR__ . '/' . '../config.php';
	}
}
