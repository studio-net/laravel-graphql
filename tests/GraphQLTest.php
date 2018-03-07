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
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$this->specify('ensure that we can call registered type', function () use ($graphql) {
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
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->posts()->saveMany(factory(Entity\Post::class, 5)->make());
		});

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$this->specify('test querying a single row', function () {
			$query = 'query { user(id: 1) { name, posts { title } }}';
			$user = Entity\User::with('posts')->find(1);
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
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$this->specify('tests datetime rfc3339 type', function () {
			$query = 'query { user(id: 1) { last_login } }';
			$data = $this->executeGraphQL($query);

			$this->assertRegExp(
				'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+|-]\d{2}:\d{2}$/',
				$data['data']['user']['last_login']
			);
		});

		$this->specify('tests json scalar type', function () {
			$query = 'query { user(id: 1) { permissions } }';
			$data = $this->executeGraphQL($query);

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
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$this->specify('tests custom query (viewer)', function () {
			$query = 'query { viewer { id, name }}';
			$user = Entity\User::first();

			$this->assertGraphQLEquals($query, [
				'data' => [
					'viewer' => [
						'id' => (string) $user->id,
						'name' => $user->name,
					]
				]
			]);
		});
	}

	/**
	 * Test camel case query converter
	 *
	 * @return void
	 */
	public function testCamelCaseQuery() {
		factory(Entity\User::class)->create();

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', [
			'query' => [
				\StudioNet\GraphQL\Tests\GraphQL\Query\Viewer::class
			]
		]);
		$graphql->registerDefinition(Definition\CamelCaseUserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$this->specify('test querying a single row with camel case fields', function () {
			$query = 'query { user(id: 1) { name, isAdmin }}';
			$user = Entity\User::find(1);

			$this->assertGraphQLEquals($query, [
				'data' => [
					'user' => [
						'name' => $user->name,
						'isAdmin' => $user->is_admin,
					]
				]
			]);
		});
	}
}
