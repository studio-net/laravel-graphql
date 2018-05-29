<?php
namespace StudioNet\GraphQL\Tests;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\GraphQL;
use StudioNet\GraphQL\Tests\Entity;

/**
 * Pagination tests
 */
class GraphQLPaginationTest extends TestCase {

	/**
	 */
	public function testOrderBy() {
		foreach (['aa','bb','cc','dd'] as $name) {
			factory(Entity\User::class)->create(['name' => $name]);
		}

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$this->specify('test order_by', function () {
			$query = <<<'EOGQL'
query {
	users(order_by: ["name_desc"]) {
		items {
				name
		}
	}
}
EOGQL;

			$res = $this->executeGraphQL($query);

			$this->assertSame(
				['dd','cc','bb','aa'],
				array_column($res['data']['users']['items'], 'name')
			);
		});
	}

	/**
	 */
	public function testOrderByWithPages() {
		foreach (['aa','bb','cc','dd','ee','ff'] as $name) {
			factory(Entity\User::class)->create(['name' => $name]);
		}

		$graphql = app(GraphQL::class);
		$graphql->registerSchema('default', []);
		$graphql->registerDefinition(Definition\UserDefinition::class);
		$graphql->registerDefinition(Definition\PostDefinition::class);
		$graphql->registerDefinition(Definition\TagDefinition::class);

		$query = <<<'EOGQL'
query ($skip: Int, $take: Int) {
	users(order_by: ["name_desc"], take: $take, skip: $skip) {
		pagination {
			totalCount
			page
			numPages
			hasNextPage
			hasPreviousPage
		}
		items {
			name
		}
	}
}
EOGQL;

		$this->specify('test with pagesize 2', function () use ($query) {
			$opts = ['variables' => ['take' => 2]];

			// Page 0
			$opts['variables']['skip'] = 0;
			$res = $this->executeGraphQL($query, $opts);
			$this->assertSame([
					'totalCount' => 6,
					'page' => 0,
					'numPages' => 3,
					'hasNextPage' => true,
					'hasPreviousPage' => false,
				], $res['data']['users']['pagination']);

			// Page 1
			$opts['variables']['skip'] = 2;
			$res = $this->executeGraphQL($query, $opts);
			$this->assertSame([
					'totalCount' => 6,
					'page' => 1,
					'numPages' => 3,
					'hasNextPage' => true,
					'hasPreviousPage' => true,
				], $res['data']['users']['pagination']);
			
			// Page 2
			$opts['variables']['skip'] = 4;
			$res = $this->executeGraphQL($query, $opts);
			$this->assertSame([
					'totalCount' => 6,
					'page' => 2,
					'numPages' => 3,
					'hasNextPage' => false,
					'hasPreviousPage' => true,
				], $res['data']['users']['pagination']);
		});

		$this->specify('test with pagesize 4', function () use ($query) {
			$opts = ['variables' => ['take' => 4]];

			// Page 0
			$opts['variables']['skip'] = 0;
			$res = $this->executeGraphQL($query, $opts);
			$this->assertSame([
					'totalCount' => 6,
					'page' => 0,
					'numPages' => 2,
					'hasNextPage' => true,
					'hasPreviousPage' => false,
				], $res['data']['users']['pagination']);

			// Page 1
			$opts['variables']['skip'] = 2;
			$res = $this->executeGraphQL($query, $opts);
			$this->assertSame([
					'totalCount' => 6,
					'page' => 1,
					'numPages' => 2,
					'hasNextPage' => false,
					'hasPreviousPage' => true,
				], $res['data']['users']['pagination']);
		});
	}
}
