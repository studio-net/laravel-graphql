<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\Label::class, function (Generator $faker) {
	return [
		'name' => $faker->word
	];
});
