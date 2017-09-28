<?php
namespace StudioNet\GraphQL\Tests\Generator\Mutation;

use GraphQL\Type\Definition\ResolveInfo;
use StudioNet\GraphQL\Generator\Mutation\NodeEloquentGenerator;
use StudioNet\GraphQL\Tests\Entity\Post;
use StudioNet\GraphQL\Tests\Entity\User;
use StudioNet\GraphQL\Tests\TestCase;

/**
 * NodeEloquentGeneratorTest
 *
 * @see TestCase
 */
class NodeEloquentGeneratorTest extends TestCase {
	/**
	 * setUp
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$graphql = $this->app['graphql'];
		$graphql->registerType('user', User::class);
	}
	/**
	 * testSupports
	 *
	 * @return void
	 */
	public function testSupports() {
		$graphql   = $this->app['graphql'];
		$generator = $this->app->make(NodeEloquentGenerator::class);

		$this->assertTrue($generator->supports($graphql->type('user')));
		$this->assertFalse($generator->supports('blabla'));
	}

	/**
	 * testKey
	 *
	 * @return void
	 */
	public function testKey() {
		$graphql   = $this->app['graphql'];
		$generator = $this->app->make(NodeEloquentGenerator::class);

		$this->assertSame('user', $generator->getKey($graphql->type('user')));
	}

	/**
	 * testGenerate
	 *
	 * @return void
	 */
	public function testGenerate() {
		$graphql   = $this->app['graphql'];
		$generator = $this->app->make(NodeEloquentGenerator::class);
		$query     = $generator->generate($graphql->type('user'));

		$this->assertArrayHasKey('args', $query);
		$this->assertArrayHasKey('type', $query);
		$this->assertArrayHasKey('resolve', $query);
		$this->assertArrayHasKey('id', $query['args']);
		$this->assertArrayHasKey('with', $query['args']);
		$this->assertArrayHasKey('name', $query['args']['with']['type']->getFields());
		$this->assertSame($graphql->type('user'), $query['type']);
	}

	/**
	 * testResolver
	 *
	 * @return void
	 */
	public function testResolver() {
		factory(User::class, 1)->create();

		$graphql   = $this->app['graphql'];
		$generator = $this->app->make(NodeEloquentGenerator::class);
		$query     = $generator->generate($graphql->type('user'));
		$resolver  = $query['resolve'];

		$response  = call_user_func_array($resolver, [null, ['id' => 1, 'with' => ['name' => 'Dupont']]]);
		$this->assertSame(User::find(1)->toArray(), $response->toArray());
	}
}
