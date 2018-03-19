<?php
namespace StudioNet\GraphQL\Tests;

use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;
use StudioNet\GraphQL\GraphQL;

// Assert class exists. Otherwise, create simple aliases in order to make tests
// working on newer PHPUnit version
if (!class_exists('PHPUnit_Framework_TestCase')) {
	class_alias(\PHPUnit\Framework\TestCase::class, 'PHPUnit_Framework_TestCase');
	class_alias(\PHPUnit\Framework\Assert::class, 'PHPUnit_Framework_Assert');
	class_alias(\PHPUnit\Framework\Constraint\Constraint::class, 'PHPUnit_Framework_Constraint');
}

/**
 * TestCase
 *
 * @see BaseTestCase
 * @abstract
 */
abstract class TestCase extends BaseTestCase {
	use \Codeception\Specify;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
	
		// Laravel 5.4 has implemented the service provider's
		// `loadMigrationsFrom' method and removes the --realpath migrate
		// option. So, we need to handle unit test for either version
		if ($this->app->version() < '5.4') {
			$this->artisan(
				'migrate',
				[
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
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => ''
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

	/**
	 * Execute GraphQL helper and return result
	 *
	 * @param  string $query
	 * @param  array $opts
	 * @return array
	 */
	public function executeGraphQL($query, array $opts = []) {
		$opts = $opts + [
			'schema' => array_get($opts, 'schema', 'default'),
			'variables' => [],
		];

		$variables = $opts['variables'];
		unset($opts['variables']);

		return app(GraphQL::class)->execute($query, $variables, $opts);
	}

	/**
	 * Assert GraphQL response is equals to $assert
	 *
	 * @param  GraphQL $graphql
	 * @param  string  $query
	 * @param  array   $assert
	 * @param  array   $opts
	 *
	 * @return void
	 */
	public function assertGraphQLEquals($query, array $assert, array $opts = []) {
		$this->assertSame($assert, $this->executeGraphQL($query, $opts));
	}
}
