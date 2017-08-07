<?php
use Faker\Generator;
use StudioNet\GraphQL\Tests\Entity;

$factory->define(Entity\User::class, function(Generator $faker) {
	// Create faked permissions
	$permissions = [
		'user.create' => $faker->boolean,
		'user.edit'   => $faker->boolean,
		'user.delete' => $faker->boolean,
		'user.view'   => $faker->boolean
	];

	return [
		'name'        => $faker->name,
		'email'       => $faker->email,
		'password'    => bcrypt(str_random(10)),
		'last_login'  => $faker->datetime(),
		'is_admin'    => $faker->boolean,
		'permissions' => $permissions
	];
});
