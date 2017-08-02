<?php
namespace StudioNet\GraphQL;

use StudioNet\GraphQL\Eloquent\QueryManager;
use StudioNet\GraphQL\Eloquent\TypeManager;
use StudioNet\GraphQL\Eloquent\MutationManager;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
		$this->loadRoutesFrom($routes);
		$this->loadViewsFrom($views, 'graphql');
		$this->publishes([$config => config_path('graphql.php')]);

		// Call external methods to load defined schemas and others things
		$this->registerScalars();
		$this->registerTransformers();
		$this->registerSchemas();
		$this->registerTypes();
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
	 * Register the application services.
	 *
	 * @return void
	 * TODO I don't thing creating manager is the good way. Need refactoring
	 */
	public function register() {
		$this->app->singleton(MutationManager::class, function($app) { return new MutationManager($app); });
		$this->app->singleton(QueryManager::class, function($app) { return new QueryManager($app); });
		$this->app->singleton(TypeManager::class, function($app) { return new TypeManager($app); });
		$this->app->singleton(GraphQL::class, function($app) { return new GraphQL($app); });

		$this->app->bind('graphql', GraphQL::class);
		$this->app->bind('graphql.eloquent.type_manager', TypeManager::class);
		$this->app->bind('graphql.eloquent.query_manager', QueryManager::class);
		$this->app->bind('graphql.eloquent.mutation_manager', MutationManager::class);
	}

	/**
	 * Get the services provided by the provider
	 *
	 * @return array
	 */
	public function provides() {
		return [
			'graphql'                       , GraphQL::class         ,
			'graphql.query_manager'         , QueryManager::class    ,
			'graphql.mutation_manager'      , MutationManager::class ,
			'graphql.eloquent.type_manager' , TypeManager::class     ,
		];
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
