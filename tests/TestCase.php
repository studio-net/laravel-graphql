<?php
namespace StudioNet\GraphQL\Tests;

use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;

// Assert class exists. Otherwise, create simple aliases in order to make tests
// working on newer PHPUnit version
if (!class_exists('PHPUnit_Framework_TestCase')) {
	class_alias(\PHPUnit\Framework\TestCase::class              , 'PHPUnit_Framework_TestCase');
	class_alias(\PHPUnit\Framework\Assert::class                , 'PHPUnit_Framework_Assert');
	class_alias(\PHPUnit\Framework\Constraint\Constraint::class , 'PHPUnit_Framework_Constraint');
}

/**
 * TestCase
 *
 * @see BaseTestCase
 * @abstract
 */
abstract class TestCase extends BaseTestCase {
	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
	
		// Laravel 5.4 has implemented the service provider's
		// `loadMigrationsFrom' method and removes the --realpath migrate
		// option. So, we need to handle unit test for either version
		if ($this->app->version() < '5.4') {
			$this->artisan('migrate', [
				'--realpath' => realpath(__DIR__ . '/' . '../database/migrations'),
				'--database' => 'testing']
			);
		} else {
			// In Laravel 5.4, no need to specify realpath
			// @see StudioNet\GraphQL\Tests\Stubs\ServiceProvider
			$this->artisan('migrate', ['--database' => 'testing']);
		}

		// Handle up factories
		$this->withFactories(dirname(__DIR__) . '/database/factories');
	}

	/**
	 * {@inheritDoc}
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	protected function getPackageProviders($app) {
		return [
			\StudioNet\GraphQL\Tests\Stubs\ServiceProvider::class
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
