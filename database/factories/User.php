<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\User::class, function(Generator $faker) {
	return [
		'name'     => $faker->name,
		'email'    => $faker->email,
		'password' => bcrypt(str_random(10))
	];
});
