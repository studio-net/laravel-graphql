<?php
namespace StudioNet\GraphQL\Tests\Generator\Query;


use StudioNet\GraphQL\Tests\TestCase;
use StudioNet\GraphQL\Generator\Query\Grammar;
use StudioNet\GraphQL\Tests\Entity\User;


/**
 * NodeEloquentGeneratorTest
 *
 * @see TestCase
 */
class GrammarTest extends TestCase {
	public function setUp() {
		parent::setUp();

		$graphql = $this->app['graphql'];
		$graphql->registerType('user', User::class);
	}

	public function testSqlGeneration() {
		$builder = User::orderBy('id', 'asc');

		$grammar = new Grammar\PostgreSQLGrammar();
		$builder = $grammar->getBuilderForFilter($builder, [
			"name" => "Daenerys",
		]);

		$this->assertSame(
			'select * from "users" where "name" = ? order by "id" asc',
			$builder->toSql());

		$builder = User::orderBy('id', 'asc');

		$builder = $grammar->getBuilderForFilter($builder, [
			"id" => "(gt) 5",
		]);
		$this->assertSame(
			'select * from "users" where "id" > ? order by "id" asc',
			$builder->toSql());

		$builder = User::orderBy('id', 'asc');

		$builder = $grammar->getBuilderForFilter($builder, [
			"name" => ["or" => ["Arya", "Sansa"]],
			"email" => "contact@winternet.net"
		]);

		$this->assertSame('select * from "users" where '
			. '(("name" = ? or "name" = ?)) and "email" = ? order by "id" asc',
			$builder->toSql());
	}

}
