<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\Country::class, function (Generator $faker) {
	return [
		'name' => $faker->country
	];
});
