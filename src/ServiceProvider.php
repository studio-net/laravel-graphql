<?php
namespace StudioNet\GraphQL;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
		$views  = $this->getConfigurationPath() . '/' . 'resources/views';

		$this->mergeConfigFrom($config, 'graphql');
		$this->loadViewsFrom($views, 'graphql');
		$this->publishes([$config => config_path('graphql.php')]);
		require $routes;

		// Call external methods to load defined schemas and others things
		$this->registerScalars();
		$this->registerTransformers();
		$this->registerSchemas();
		$this->registerTypes();
		$this->registerGenerators();
	}

	/**
	 * Register schemas
	 *
	 * @return void
	 */
	public function registerSchemas() {
		$schemas = config('graphql.schema.definitions', []);

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
		$types = config('graphql.type', []);

		foreach ($types as $name => $type) {
			$this->app['graphql']->registerType($name, $type);
		}
	}

	/**
	 * Register scalar
	 *
	 * @return void
	 */
	public function registerScalars() {
		$scalars = config('graphql.scalar', []);

		foreach ($scalars as $name => $scalar) {
			$this->app['graphql']->registerScalar($name, $scalar);
		}
	}

	/**
	 * Register transformers
	 *
	 * @return void
	 */
	public function registerTransformers() {
		$transformers = config('graphql.transformer', []);

		foreach ($transformers as $key => $many) {
			foreach ($many as $transformer) {
				$this->app['graphql']->registerTransformer($key, $transformer);
			}
		}
	}

	/**
	 * Register generators
	 *
	 * @return void
	 */
	public function registerGenerators() {
		$generators = config('graphql.generator', []);

		foreach ($generators as $key => $many) {
			foreach ($many as $generator) {
				$this->app['graphql']->registerGenerator($key, $generator);
			}
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
