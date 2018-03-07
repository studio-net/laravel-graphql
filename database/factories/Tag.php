<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\Tag::class, function (Generator $faker) {
	return [
		'name' => $faker->word,
	];
});
