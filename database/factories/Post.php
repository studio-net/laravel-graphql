<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\Post::class, function (Generator $faker) {
	return [
		'title' => $faker->catchPhrase,
		'content' => $faker->text()
	];
});
