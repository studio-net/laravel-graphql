<?php
namespace StudioNet\GraphQL\Tests;

use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\GraphQL;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
		app(GraphQL::class)->registerType('\\Test\\Class\\Type');
	}
}
