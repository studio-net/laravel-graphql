<?php
namespace StudioNet\GraphQL\Support\Eloquent;

use GraphQL\Type\Definition\Type as GraphQLType;
use StudioNet\GraphQL\Support\Eloquent\ModelAttributes;
use StudioNet\GraphQL\Tests\Entity\User;
use StudioNet\GraphQL\Tests\TestCase;

/**
 * ModelAttributesTest
 *
 * @see TestCase
 */
class ModelAttributesTest extends TestCase {
	/**
	 * testRelations
	 *
	 * @return void
	 */
	public function testRelations() {
		$model     = $this->app->make(User::class);
		$relations = $this->app->make(ModelAttributes::class)->getRelations($model);

		// Assert array are matching : subset is present in the array
		$this->assertArraySubset([
			'posts' => [
				'field' => 'posts',
				'type'  => 'HasMany',
				'model' => 'StudioNet\GraphQL\Tests\Entity\Post'
			]
		], $relations);
	}

	/**
	 * testColumns
	 *
	 * @return void
	 */
	public function testColumns() {
		$model   = $this->app->make(User::class);
		$columns = $this->app->make(ModelAttributes::class)->getColumns($model);

		// Assert array are matching : subset is present in the array
		$this->assertArraySubset([
			'posts'       => null,
			'id'          => GraphQLType::id(),
			'name'        => GraphQLType::string(),
			'email'       => GraphQLType::string(),

			// Test field conversion (using $casts)
			'is_admin'    => GraphQLType::boolean(),

			// Test custom registered scalars type
			'permissions' => $this->app['graphql']->scalar('array'),
			'last_login'  => $this->app['graphql']->scalar('timestamp')
		], $columns);
	}
}
