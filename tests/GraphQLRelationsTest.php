<?php

namespace StudioNet\GraphQL\Tests;

use Illuminate\Support\Facades\DB;
use StudioNet\GraphQL\Tests\Entity;

/**
 * Tests for relations resolving.
 * The main point of this tests is to make sure, that eager loading works, also in deep queries.
 * The correctness of returned GraphQL response is not tested.
 *
 * @see TestCase
 */
class GraphQLRelationsTest extends TestCase {
	/**
	 * Test 1-1 Relation
	 *
	 * @return void
	 */
	public function testOneToOneRelation() {
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->phone()->save(factory(Entity\Phone::class)->make());
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing 1-1 relation', function () {
			// fetch data directly
			Entity\User::with('phone')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { users { items {name phone { label number } }}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}

	/**
	 * Test 1-1 Relation inverse
	 *
	 * @return void
	 */
	public function testOneToOneRelationInverse() {
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->phone()->save(factory(Entity\Phone::class)->make());
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing 1-1 relation inverse', function () {
			// fetch data directly
			Entity\Phone::with('user')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { phones { items { label number user { name } }}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}


	/**
	 * Test 1-* Relation
	 *
	 * @return void
	 */
	public function testOneToManyRelation() {
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->posts()->saveMany(factory(Entity\Post::class, 5)->make());
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing 1-* relation', function () {
			// fetch data directly
			Entity\User::with('posts')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { users { items {name posts { title content } }}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}


	/**
	 * Test 1-* Relation inverse
	 *
	 * @return void
	 */
	public function testOneToManyRelationInverse() {
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->posts()->saveMany(factory(Entity\Post::class, 5)->make());
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing 1-* relation inverse', function () {
			// fetch data directly
			Entity\Post::with('author')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { posts { items {title content author { name } }}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}


	/**
	 * Test HasManyThrough Relation
	 *
	 * @return void
	 */
	public function testHasManyThroughRelation() {
		factory(Entity\Country::class, 2)->create()->each(function ($country) {
			factory(Entity\User::class, 2)->create([
				'country_id' => $country->id
			])->each(function ($user) {
				factory(Entity\Post::class, 5)->create([
					'user_id' => $user->id
				]);
			});
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing HasManyThrough relation', function () {
			// fetch data directly
			Entity\Country::with('posts')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { countries { items { name posts { title content }}}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}

	/**
	 * Test Polymorphic 1-1 Relation
	 *
	 * @return void
	 */
	public function testPolymorphicOneToOneRelation() {
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->comments()->saveMany(factory(Entity\Comment::class, 5)->make());
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing polymorphic 1-1 relation', function () {
			// fetch data directly
			Entity\User::with('comments')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { users { items { name comments { body }}}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}

	/**
	 * Test Polymorphic *-* Relation
	 *
	 * @return void
	 */
	public function testPolymorphicManyToManyRelation() {
		factory(Entity\User::class, 5)->create()->each(function ($user) {
			$user->labels()->saveMany(factory(Entity\Label::class, 5)->make());
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing polymorphic *-* relation', function () {
			// fetch data directly
			Entity\User::with('labels')->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { users { items { name labels { name }}}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}

	/**
	 * Test Polymorphic *-* Relation inverse
	 *
	 * @return void
	 */
	public function testPolymorphicManyToManyRelationInverse() {
		factory(Entity\User::class, 4)->create()->each(function ($user) {
			$user->labels()->saveMany(factory(Entity\Label::class, 3)->make());
			factory(Entity\Post::class, 2)->create([
				'user_id' => $user->id
			])->each(function ($post) {
				$post->labels()->saveMany(factory(Entity\Label::class, 2)->make());
			});
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing polymorphic *-* relation inverse', function () {
			// fetch data directly
			Entity\Label::with(['users', 'posts'])->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { labels { items { name users { name } posts { title } }}}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}


	/**
	 * Test deep relations
	 *
	 * @return void
	 */
	public function testDeepRelations() {
		factory(Entity\Country::class, 2)->create()->each(function ($country) {
			factory(Entity\User::class, 2)->create([
				'country_id' => $country->id
			])->each(function ($user) {
				$user->phone()->save(factory(Entity\Phone::class)->make());
				factory(Entity\Post::class, 2)->create([
					'user_id' => $user->id
				])->each(function ($post) {
					$post->labels()->saveMany(factory(Entity\Label::class, 2)->make());
					$post->tags()->saveMany(factory(Entity\Tag::class, 2)->make());
				});
			});
		});

		$this->registerAllDefinitions();

		// enable query log for comparing queries
		DB::enableQueryLog();

		$this->specify('Testing polymorphic *-* relation inverse', function () {
			// fetch data directly
			Entity\Country::with(['posts', 'posts.labels', 'posts.tags', 'posts.author', 'posts.author.phone'])->get();

			// get query log and remove 'time' property from each query-element
			$madeQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			DB::flushQueryLog();

			// fetch data with graphql
			$query = 'query { 
				countries { 
					items { 
						name 
						posts { 
							title 
							labels { 
								name 
							} 
							tags { 
								name 
							} 
							author { 
								name 
								phone {
									label 
									number 
								}
							} 
						}
					}
				}
			}';
			$this->executeGraphQL($query);

			// get query log produced duricng fetching over graphql and remove 'time' property from each query-element
			$gqlQueries = $this->removeTimeElementFromQueryLog(DB::getQueryLog());

			// queries should be the same
			$this->assertSame($madeQueries, $gqlQueries);
		});
	}


	private function removeTimeElementFromQueryLog(array $queryLog) {
		foreach ($queryLog as &$item) {
			unset($item['time']);
		}
		// unset item reference to prevent unexpected stuff
		unset($item);

		return $queryLog;
	}
}
