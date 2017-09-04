<?php
namespace StudioNet\GraphQL\Tests\Generator\Query;

use GraphQL\Type\Definition\ResolveInfo;
use StudioNet\GraphQL\Generator\Query\NodeEloquentGenerator;
use StudioNet\GraphQL\Tests\Entity\Post;
use StudioNet\GraphQL\Tests\Entity\User;
use StudioNet\GraphQL\Tests\TestCase;

/**
 * NodeEloquentGeneratorTest
 *
 * @see TestCase
 */
class NodeEloquentGeneratorTest extends TestCase {
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
		$this->assertSame($graphql->type('user'), $query['type']);
	}

	/**
	 * testResolver
	 *
	 * @return void
	 */
	public function testResolver() {
		factory(User::class, 5)->create()->each(function($user) {
			$user->posts()->saveMany(factory(Post::class, 5)->make());
		});

		$info = $this->getMockBuilder(ResolveInfo::class)
			->disableOriginalConstructor()
			->setMethods(['getFieldSelection'])
			->getMock();

		$info->method('getFieldSelection')->willReturn(['posts' => ['title']]);

		$graphql   = $this->app['graphql'];
		$generator = $this->app->make(NodeEloquentGenerator::class);
		$query     = $generator->generate($graphql->type('user'));
		$resolver  = $query['resolve'];

		$response  = call_user_func_array($resolver, [null, ['id' => 2], [], $info]);
		$this->assertSame(User::with('posts')->find(2)->toArray(), $response->toArray());
	}
}
