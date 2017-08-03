<?php
namespace StudioNet\GraphQL\Tests;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use StudioNet\GraphQL\GraphQL;
use StudioNet\GraphQL\Tests\Entity;
use StudioNet\GraphQL\Transformer\Transformer;

/**
 * Singleton tests
 *
 * @see TestCase
 */
class GraphQLTest extends TestCase {
	use DatabaseTransactions;

	/**
	 * testGetSchemaException
	 *
	 * @return void
	 * @expectedException \StudioNet\GraphQL\Exception\SchemaNotFoundException
	 */
	public function testGetSchemaException() {
		app(GraphQL::class)->getSchema('test');
	}

	/**
	 * testRegistertTypeException
	 *
	 * @return void
	 * @expectedException \StudioNet\GraphQL\Exception\TypeNotFoundException
	 */
	public function testRegisterTypeException() {
		app(GraphQL::class)->registerType(null, '\\Test\\Class\\Type');
	}

	/**
	 * testRegisterType
	 *
	 * @return void
	 */
	public function testRegisterType() {
		$graphql = app(GraphQL::class);
		$graphql->registerType('user', Entity\User::class);
		$graphql->registerType('post', Entity\Post::class);

		$this->assertInstanceOf(ObjectType::class, $graphql->type('user'));
		$this->assertInstanceOf(ObjectType::class, $graphql->type('post'));
		$this->assertInstanceOf(ListOfType::class, $graphql->listOf('user'));
		$this->assertInstanceOf(ListOfType::class, $graphql->listOf('post'));
	}

	/**
	 * testEndpoint
	 *
	 * @return void
	 */
	public function testEndpoint() {
		factory(Entity\User::class, 5)->create()->each(function($user) {
			$user->posts()->saveMany(factory(Entity\Post::class, 5)->make());
		});

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerType('user', Entity\User::class);
		$graphql->registerType('post', Entity\Post::class);

		$params   = ['query' => 'query { user(id: 1) { name, posts { title } }}'];
		$response = $this->call('GET', '/graphql', $params);
		$content  = $response->getData(true);
		$user     = Entity\User::with('posts')->find(1);
		$posts    = [];

		foreach ($user->posts as $post) {
			$posts[]['title'] = $post->title;
		}

		$this->assertArrayHasKey('data', $content);
		$this->assertArrayNotHasKey('errors', $content);
		$this->assertSame([
			'user' => [
				'name'  => $user->name,
				'posts' => $posts
			]
		], $content['data']);
	}
}
