<?php
namespace StudioNet\GraphQL;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use StudioNet\GraphQL\Cache\CachePool;
use StudioNet\GraphQL\Support\Eloquent\ModelAttributes;

/**
 * ServiceProvider
 *
 * @see BaseServiceProvider
 */
class ServiceProvider extends BaseServiceProvider {
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {
		$config = $this->getConfigurationPath() . '/' . 'resources/config.php';
		$routes = $this->getConfigurationPath() . '/' . 'resources/routes.php';
		$views = $this->getConfigurationPath() . '/' . 'resources/views';

		$this->mergeConfigFrom($config, 'graphql');
		$this->loadViewsFrom($views, 'graphql');
		$this->publishes([$config => config_path('graphql.php')]);
		require $routes;

		// Call external methods to load defined schemas and others things
		$this->registerSchemas();
		$this->registerDefinitions();
	}

	/**
	 * Register schemas
	 *
	 * @return void
	 */
	private function registerSchemas() {
		$schemas = config('graphql.schema.definitions', []);

		foreach ($schemas as $name => $data) {
			$this->app['graphql']->registerSchema($name, $data);
		}
	}

	/**
	 * Register definitions
	 *
	 * @return void
	 */
	private function registerDefinitions() {
		$definitions = config('graphql.definitions', []);

		foreach ($definitions as $definition) {
			$this->app['graphql']->registerDefinition($definition);
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton(CachePool::class, function () { return new CachePool; });
		$this->app->singleton(GraphQL::class);
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
