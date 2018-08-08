<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\Phone::class, function (Generator $faker) {
	return [
		'label' => $faker->word,
		'number' => $faker->phoneNumber
	];
});
