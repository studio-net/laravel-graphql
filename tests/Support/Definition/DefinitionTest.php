<?php
namespace StudioNet\GraphQL\Tests\Support\Definition;

use StudioNet\GraphQL\Tests\TestCase;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use StudioNet\GraphQL\Tests\Entity\User;

/**
 * Test `Definition` class
 *
 * @see TestCase
 */
class DefinitionTest extends TestCase {
	/**
	 * Return mocked definition
	 *
	 * @param  string $name
	 * @param  string $description
	 * @param  array  $fields
	 *
	 * @return Definition
	 */
	public function getDefinitionMock($name, $description, array $fields = []) {
		$stub = $this->getMockBuilder(Definition::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->setMethods(['getName', 'getDescription', 'getFetchable', 'getMutable', 'getSource'])
			->getMock();

		$stub->method('getName')->willReturn($name);
		$stub->method('getSource')->willReturn(User::class);
		$stub->method('getDescription')->willReturn($description);
		$stub->method('getFetchable')->willReturn(array_get($fields, 'fetchable', []));
		$stub->method('getMutable')->willReturn(array_get($fields, 'mutable', []));

		return $stub;
	}

	/**
	 * Ensure that the resolve type return a correct data
	 *
	 * @return void
	 */
	public function testResolveType() {
		$definition = $this->getDefinitionMock('User', 'a User description', [
			'fetchable' => [
				'id'   => Type::nonNull(Type::id()),
				'name' => Type::string()
			]
		]);

		$this->specify('tests that the resolveType will return an ObjectType', function() use ($definition) {
			$object = $definition->resolveType();

			$this->assertInstanceOf(ObjectType::class, $object);
			$this->assertTrue(is_callable($object->config['fields']));
		});

		$this->specify('tests that the resolveType will have corresponding fields, name and description', function() use ($definition) {
			$object = $definition->resolveType();
			$fields = call_user_func($object->config['fields']);

			$this->assertSame('User', $object->name);
			$this->assertSame('a User description', $object->description);
			$this->assertArraySubset([
				'id'   => ['type' => Type::nonNull(Type::id())],
				'name' => ['type' => Type::string()]
			], $fields);
		});
	}

	/**
	 * Test transformers attributes and method
	 *
	 * @return void
	 */
	public function testTransformers() {
		$definition = $this->getDefinitionMock('User', 'a User description');

		$this->specify('asserts that the definition handles all transformers', function() use ($definition) {
			$this->assertArrayHasKey('list'  , $definition->transformers);
			$this->assertArrayHasKey('view'  , $definition->transformers);
			$this->assertArrayHasKey('drop'  , $definition->transformers);
			$this->assertArrayHasKey('store' , $definition->transformers);
			$this->assertArrayHasKey('batch' , $definition->transformers);
		});

		$this->specify('ensure getTransformers method return all $transformers elements', function() use ($definition) {
			$transformers = $definition->getTransformers();

			foreach (array_keys($transformers) as $transformer) {
				$this->assertArrayHasKey($transformer, $definition->transformers);
			}
		});
	}
}
