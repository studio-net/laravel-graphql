<?php
namespace StudioNet\GraphQL\Tests\Transformer;

use StudioNet\GraphQL\Tests\Entity;
use StudioNet\GraphQL\Tests\TestCase;
use StudioNet\GraphQL\Transformer\Type\ModelTransformer;
use StudioNet\GraphQL\Definition\Type\EloquentObjectType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * ModelTransformerTest
 *
 * @see TestCase
 */
class ModelTransformerTest extends TestCase {
	use DatabaseTransactions;

	/**
	 * testSupports
	 *
	 * @return void
	 */
	public function testSupports() {
		$transformer = $this->app->make(ModelTransformer::class);

		$this->assertTrue($transformer->supports($this->app->make(Entity\User::class)));
		$this->assertTrue($transformer->supports($this->app->make(Entity\Post::class)));
		$this->assertFalse($transformer->supports('blabla'));
	}

	/**
	 * testTransform
	 *
	 * @return void
	 */
	public function testTransform() {
		$user = $this->app->make(Entity\User::class);
		$transformer = $this->app->make(ModelTransformer::class);
		$object = $transformer->transform($user);
		$fields = $object->getFields();

		$this->assertInstanceOf(EloquentObjectType::class, $object);
		$this->assertSame('User', $object->name);
		$this->assertSame('A User model representation', $object->description);
		$this->assertSame($user, $object->getModel());

		foreach (['id', 'name', 'email', 'posts', 'created_at', 'updated_at'] as $field) {
			$this->assertArrayHasKey($field, $fields);
		}
	}

	/**
	 * testRelationshipColumnArguments
	 *
	 * @return void
	 */
	public function testRelationshipColumnArguments() {
		$user        = $this->app->make(Entity\User::class);
		$transformer = $this->app->make(ModelTransformer::class);
		$args        = $transformer->transform($user)->getFields()['posts']->config['args'];
		$assert      = [
			'after'  => \GraphQL\Type\Definition\IDType::class,
			'before' => \GraphQL\Type\Definition\IDType::class,
			'skip'   => \GraphQL\Type\Definition\IntType::class,
			'take'   => \GraphQL\Type\Definition\IntType::class
		];

		foreach ($assert as $argument => $type) {
			$this->assertArrayHasKey($argument, $args);
			$this->assertInstanceOf($type, $args[$argument]['type']);
		}
	}

	/**
	 * testResolve
	 *
	 * @return void
	 */
	public function testResolve() {
		factory(Entity\User::class, 5)->create()->each(function($user) {
			$user->posts()->saveMany(factory(Entity\Post::class, 5)->make());
		});

		$builder     = Entity\User::class;
		$user        = $this->app->make($builder);
		$entity      = $builder::with('posts')->find(1);
		$transformer = $this->app->make(ModelTransformer::class);
		$resolver    = $transformer->transform($user)->getFields()['posts']->config['resolve'];
		$response    = call_user_func_array($resolver, [$entity, ['take' => 1, 'skip' => 1]]);

		$this->assertSame(1, count($response));
		$this->assertSame($entity->posts->get(1)->toArray(), current($response)->toArray());
	}
}
