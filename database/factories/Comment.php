<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\Comment::class, function (Generator $faker) {
	return [
		'body' => $faker->text(100)
	];
});
