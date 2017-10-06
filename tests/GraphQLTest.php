<?php
namespace StudioNet\GraphQL\Tests;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\GraphQL;
use StudioNet\GraphQL\Tests\Entity;

/**
 * Singleton tests
 *
 * @see TestCase
 */
class GraphQLTest extends TestCase {
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
	 * testRegisterType
	 *
	 * @return void
	 */
	public function testRegisterDefinition() {
		$graphql = app(GraphQL::class);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);

		$this->specify('ensure that we can call registered type', function() use ($graphql) {
			$this->assertInstanceOf(ObjectType::class, $graphql->type('user'));
			$this->assertInstanceOf(ObjectType::class, $graphql->type('post'));
			$this->assertInstanceOf(ListOfType::class, $graphql->listOf('user'));
			$this->assertInstanceOf(ListOfType::class, $graphql->listOf('post'));
		});
	}

	/**
	 * testEndpoint
	 *
	 * @return void
	 */
	public function testQuery() {
		factory(Entity\User::class, 5)->create()->each(function($user) {
			$user->posts()->saveMany(factory(Entity\Post::class, 5)->make());
		});

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);

		$this->specify('test querying a single row', function() {
			$query = 'query { user(id: 1) { name, posts { title } }}';
			$user  = Entity\User::with('posts')->find(1);
			$posts = [];

			foreach ($user->posts as $post) {
				$posts[]['title'] = $post->title;
			}

			$this->assertGraphQLEquals($query, [
				'data' => [
					'user' => [
						'name' => $user->name,
						'posts' => $posts
					]
				]
			]);
		});
	}

	/**
	 * Test mutation
	 *
	 * @return void
	 */
	public function testMutation() {
		factory(Entity\User::class, 5)->create();
		
		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);

		$this->specify('tests mutation on user', function() {
			$query = 'mutation { user(id: 1, with: { name: "toto" }) { id, name } }';
			$this->assertGraphQLEquals($query, [
				'data' => [
					'user' => [
						'id'   => '1',
						'name' => 'toto',
					]
				]
			]);

			$user = Entity\User::first();
			$this->assertSame('toto', $user->name);
		});

		$this->specify('tests drop on user', function() {
			$query = 'mutation { deleteUser(id: 1) { name }}';
			$this->assertGraphQLEquals($query, [
				'data' => [
					'deleteUser' => [
						'name' => 'toto',
					]
				]
			]);

			$user = Entity\User::find(1);
			$this->assertEmpty($user);
		});

		$this->specify('tests batch update on user', function() {
			$query = 'mutation { users(objects: [{id: 4, with: {name: "test"}}, {id: 5, with: {name: "toto"}}]) { id, name }}';
			$this->assertGraphQLEquals($query, [
				'data' => [
					'users' => [
						['id' => '4', 'name' => 'test'],
						['id' => '5', 'name' => 'toto'],
					]
				]
			]);
		});
	}

	/**
	 * Test implemented scalar
	 *
	 * @return void
	 */
	public function testScalar() {
		factory(Entity\User::class)->create();

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);

		$this->specify('tests datetime scalar type', function() {
			$query = 'query { user(id: 1) { last_login } }';
			$data  = $this->executeGraphQL($query);

			$this->assertInternalType('int', $data['data']['user']['last_login']);
		});

		$this->specify('tests json scalar type', function() {
			$query = 'query { user(id: 1) { permissions } }';
			$data  = $this->executeGraphQL($query);

			$this->assertInternalType('array', $data['data']['user']['permissions']);
		});
	}

	/**
	 * Test custom query
	 *
	 * @return void
	 */
	public function testCustomQuery() {
		factory(Entity\User::class)->create();

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', [
			'query' => [
				\StudioNet\GraphQL\Tests\GraphQL\Query\Viewer::class
			]
		]);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);

		$this->specify('tests custom query (viewer)', function() {
			$query = 'query { viewer { id, name }}';
			$user  = Entity\User::first();

			$this->assertGraphQLEquals($query, [
				'data' => [
					'viewer' => [
						'id'   => (string) $user->id,
						'name' => $user->name,
					]
				]
			]);
		});
	}
}
