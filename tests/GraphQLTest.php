<?php
namespace StudioNet\GraphQL\Tests;

use StudioNet\GraphQL\Tests\Entity\User;
use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\GraphQL;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GraphQLTest extends TestCase {
	use DatabaseTransactions;
	use Traits\EloquentTypeStubTrait;

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
		app(GraphQL::class)->registerType('\\Test\\Class\\Type');
	}

	/**
	 * testGetSchema
	 *
	 * @return void
	 */
	public function testGetSchema() {
		$stub = $this->getEloquentTypeStub(User::class, 'user', 'a user type');
		$stub->method('getFields')->willReturn([
			'name'  => GraphQLType::string(),
			'email' => GraphQLType::string()
		]);

		$graphql = app(GraphQL::class);
		$graphql->registerType($stub);
		$graphql->registerSchema('default', []);

		$schema = $graphql->getSchema('default');
		$fields = $schema->getQueryType()->getFields();

		$this->assertArrayHasKey('user', $fields);
		$this->assertArrayHasKey('users', $fields);
	}

	/**
	 * testExecution
	 *
	 * @return void
	 */
	public function testExecution() {
		$stub = $this->getEloquentTypeStub(User::class, 'user', 'a user type');
		$stub->method('getFields')->willReturn([
			'name'  => GraphQLType::string(),
			'email' => GraphQLType::string()
		]);

		factory(User::class, 10)->create();

		$query   = 'query { users(take:2) { name, email }}';
		$graphql = app(GraphQL::class);
		$graphql->registerType($stub);
		$graphql->registerSchema('default', []);

		$response = $graphql->execute($query, [], ['schema' => 'default']);
		$this->assertSame(2, count($response['data']['users']));
		$this->assertNotEmpty(array_first($response['data']['users']));
	}
}
