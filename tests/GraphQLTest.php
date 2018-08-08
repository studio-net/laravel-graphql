<?php
namespace StudioNet\GraphQL\Tests;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use StudioNet\GraphQL\GraphQL;
use StudioNet\GraphQL\Tests\Entity;
use StudioNet\GraphQL\Tests\GraphQL\Query\Viewer;

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
		$this->registerAllDefinitions();

		$graphql = app(GraphQL::class);

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

		$this->registerAllDefinitions();

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

		$this->registerAllDefinitions();

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

		$this->registerAllDefinitions([
			'query' => [
				Viewer::class
			]
		]);

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

		$this->registerAllDefinitionsCamelCase([
			'query' => [
				Viewer::class
			]
		]);

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

	/**
	 * Test filters : equality
	 */
	public function testFiltersEquality() {
		factory(Entity\User::class, 2)->create();

		$this->registerAllDefinitions();

		$this->specify('test equality filtering', function () {
			$query = <<<'EOGQL'
query ($filter: UserFilter) {
	users(filter: $filter) {
		items {
			name
		}
	}
}
EOGQL;
			$user = Entity\User::find(1);

			$this->assertGraphQLEquals(
				$query,
				[
				'data' => [
					'users' => [
						'items' => [
							[
								'name' => $user->name
							]
						]
					]
				]
			],
			[
				'variables' => [
					'filter' => [
						'id' => '1'
					]
				]
			]
			);
		});
	}

	/**
	 * Test filters : equality
	 */
	public function testFiltersContains() {
		factory(Entity\User::class, 3)->create();

		$this->registerAllDefinitions();

		$this->specify('test equality containing', function () {
			$query = <<<'EOGQL'
query ($filter: UserFilter) {
	users(filter: $filter) {
		items { id }
	}
}
EOGQL;

			$res = $this->executeGraphQL($query, [
				'variables' => [
					'filter' => [
						'id' => ['1','3']
					]
				]
			]);

			$this->assertSame(
				['1','3'],
				array_column($res['data']['users']['items'], 'id')
			);
		});
	}

	/**
	 * Test filters : custom
	 */
	public function testFiltersCustom() {
		factory(Entity\User::class)->create(['name' => 'foo']);
		factory(Entity\User::class)->create(['name' => 'bar']);
		factory(Entity\User::class)->create(['name' => 'baz']);
		factory(Entity\User::class)->create(['name' => 'foobar']);

		$this->registerAllDefinitions();

		$this->specify('test equality custom', function () {
			// We should only get users which name starts with 'ba'
			$query = <<<'EOGQL'
query ($filter: UserFilter) {
	users(filter: $filter) {
		items {
				name
		}
	}
}
EOGQL;

			$res = $this->executeGraphQL($query, [
				'variables' => [
					'filter' => [
						'nameLike' => 'ba%'
					]
				]
			]);

			$this->assertSame(
				['bar','baz'],
				array_column($res['data']['users']['items'], 'name')
			);
		});
	}
}
