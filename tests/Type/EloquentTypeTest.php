<?php
namespace StudioNet\GraphQL\Tests\Type;

use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use StudioNet\GraphQL\Tests\Traits\EloquentTypeStubTrait;
use StudioNet\GraphQL\Tests\Entity\User;
use StudioNet\GraphQL\Tests\TestCase;

class EloquentTypeTest extends TestCase {
	use DatabaseTransactions;
	use EloquentTypeStubTrait;

	/**
	 * testArguments
	 *
	 * @return void
	 */
	public function testArguments() {
		$stub = $this->getEloquentTypeStub(User::class, 'user', 'a user graphql type');

		$this->assertArrayHasKey('take'   , $stub->getArguments());
		$this->assertArrayHasKey('skip'   , $stub->getArguments());
		$this->assertArrayHasKey('before' , $stub->getArguments());
		$this->assertArrayHasKey('after'  , $stub->getArguments());
		$this->assertArrayHasKey('id'     , $stub->getArguments());
	}

	/**
	 * testResolve
	 *
	 * @return void
	 */
	public function testResolve() {
		factory(User::class, 10)->create();

		$stub = $this->getEloquentTypeStub(User::class, 'user', 'a user graphql type');
		$stub->method('getFields')->willReturn([
			'name' => GraphQLType::string()
		]);

		$data = $stub->resolve(null, ['take' => 2]);
		$this->assertSame(2, $data->count());

		$data = $stub->resolve(null, ['after' => 8]);
		$this->assertSame(2, $data->count());

		// Assert resolve single entry : prevent showing hidden fields
		$data = $stub->resolve(null, ['id' => 1]);
		$user = User::find(1);
		$this->assertInstanceOf(User::class, $data);
		$this->assertSame($user->name, $data->name);
		$this->assertArrayNotHasKey('password', $data->toArray());
	}
}
