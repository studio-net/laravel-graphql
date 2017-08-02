<?php
namespace StudioNet\GraphQL\Tests;

use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use StudioNet\GraphQL\GraphQL;
use StudioNet\GraphQL\Transformer\Transformer;

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
}
