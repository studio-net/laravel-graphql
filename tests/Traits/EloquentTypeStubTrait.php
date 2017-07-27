<?php
namespace StudioNet\GraphQL\Tests\Traits;

use StudioNet\GraphQL\Support\EloquentType;

trait EloquentTypeStubTrait {
	/**
	 * Return EloquentType stub
	 *
	 * @param  string $cls
	 * @param  string $name
	 * @param  string $description
	 * @return EloquentType
	 */
	public function getEloquentTypeStub($cls, $name = '', $description = '') {
		$stub = $this->getMockBuilder(EloquentType::class)
			->disableOriginalConstructor()
			->setMethods(['getFields', 'getName', 'getDescription', 'getEntityClass'])
			->getMock();

		$stub->method('getEntityClass')->willReturn($cls);
		$stub->method('getName')->willReturn($name);
		$stub->method('getDescription')->willReturn($description);

		return $stub;
	}
}
