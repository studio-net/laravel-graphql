<?php
namespace StudioNet\GraphQL\Tests\Transformer;

use StudioNet\GraphQL\Tests\Entity;
use StudioNet\GraphQL\Tests\TestCase;
use StudioNet\GraphQL\Support\Field;
use StudioNet\GraphQL\Transformer\FieldTransformer;
use GraphQL\Type\Definition\Type as GraphQLType;

/**
 * FieldTransformerTest
 *
 * @see TestCase
 */
class FieldTransformerTest extends TestCase {
	/**
	 * @override
	 */
	public function setUp() {
		parent::setUp();

		$this->app['graphql']->registerType('user', Entity\User::class);
	}

	/**
	 * getQueryStub
	 *
	 * @return void
	 */
	public function getQueryStub() {
		$stub = $this->getMockBuilder(Field::class)
			->setMethods(['getRelatedType', 'getArguments'])
			->getMock();

		$stub->method('getRelatedType')->willReturn($this->app['graphql']->type('user'));
		$stub->method('getArguments')->willReturn(['id' => GraphQLType::id()]);

		return $stub;
	}

	/**
	 * testSupports
	 *
	 * @return void
	 */
	public function testSupports() {
		$transformer = $this->app->make(FieldTransformer::class);

		$this->assertFalse($transformer->supports($this->app->make(Entity\User::class)));
		$this->assertTrue($transformer->supports($this->getQueryStub()));
	}

	/**
	 * testTransform
	 *
	 * @return void
	 */
	public function testTransform() {
		$query = $this->getQueryStub();
		$transformer = $this->app->make(FieldTransformer::class);
		$object = $transformer->transform($query);

		$this->assertArrayNotHasKey('resolve', $object);
		$this->assertSame($this->app['graphql']->type('user'), $object['type']);
		$this->assertSame(['id' => GraphQLType::id()], $object['args']);
	}
}
