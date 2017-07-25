<?php
namespace StudioNet\GraphQL\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
	
		$this->loadLaravelMigrations(['--database' => 'testing']);
		$this->withFactories(dirname(__DIR__) . '/database/factories');
	}

	/**
	 * {@inheritDoc}
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function getPackageProviders($app) {
		return [
			\StudioNet\GraphQL\ServiceProvider::class
		];
	}

	/**
	 * {@inheritDoc}
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function getPackageAliases($app) {
		return [
			'GraphQL' => \StudioNet\GraphQL\Support\Facades\GraphQL::class
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getEnvironmentSetUp($app) {
		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver'   => 'sqlite',
			'database' => ':memory:',
			'prefix'   => ''
		]);
	}

	/**
	 * Assert that $schema is an instance of GraphQL\Schema
	 *
	 * @param  mixed $schema
	 * @return void
	 */
	public function assertGraphQLSchema($schema) {
		$this->assertInstanceOf(\GraphQL\Schema::class, $schema);
	}
}
