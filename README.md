Laravel GraphQL
===============

Use Facebook GraphQL with Laravel 5.2 >=. It is based on the PHP
implementation [here](https://github.com/webonyx/graphql-php). You can find more
information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
on the [React](http://facebook.github.io/react) blog or you can read the
[GraphQL specifications](https://facebook.github.io/graphql/).

[![Latest Stable Version](https://poser.pugx.org/studio-net/laravel-graphql/v/stable)](https://packagist.org/packages/studio-net/laravel-graphql)
[![Latest Unstable Version](https://poser.pugx.org/studio-net/laravel-graphql/v/unstable)](https://packagist.org/packages/studio-net/laravel-graphql)
[![Total Downloads](https://poser.pugx.org/studio-net/laravel-graphql/downloads)](https://packagist.org/packages/studio-net/laravel-graphql)
[![Monthly Downloads](https://poser.pugx.org/studio-net/laravel-graphql/d/monthly)](https://packagist.org/packages/studio-net/laravel-graphql)
[![Daily Downloads](https://poser.pugx.org/studio-net/laravel-graphql/d/daily)](https://packagist.org/packages/studio-net/laravel-graphql)
[![License](https://poser.pugx.org/studio-net/laravel-graphql/license)](https://packagist.org/packages/studio-net/laravel-graphql)
[![Build Status](https://api.travis-ci.org/studio-net/laravel-graphql.svg?branch=master)](https://travis-ci.org/studio-net/laravel-graphql)

## Installation

```bash
composer require studio-net/laravel-graphql @dev
```

If you're not using Laravel 5.5, don't forget to append facade and service
provider to you `config/app.php` file. Next, you have to publish vendor.

```bash
php artisan vendor:publish --provider="StudioNet\GraphQL\ServiceProvider"
```

## Usage

- [Definition](#definition)
- [Query](#query)
- [Mutation](#mutation)
- [Self documentation](#self-documentation)
- [Examples](#examples)

### Definition

Each source of data must have a corresponding definition in order to retrieve
fetchable and mutable fields.

```php
# app/GraphQL/Definition/UserDefinition.php

namespace App\GraphQL\Definition;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\EloquentDefinition;
use StudioNet\GraphQL\Filter\EqualsOrContainsFilter;
use App\User;

/**
 * Specify user GraphQL definition
 *
 * @see EloquentDefinition
 */
class UserDefinition extends EloquentDefinition {
	/**
	 * Set a name to the definition. The name will be lowercase in order to
	 * retrieve it with `\GraphQL::type` or `\GraphQL::listOf` methods
	 *
	 * @return string
	 */
	public function getName() {
		return 'User';
	}

	/**
	 * Set a description to the definition
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Represents a User';
	}

	/**
	 * Represents the source of the data. Here, Eloquent model
	 *
	 * @return string
	 */
	public function getSource() {
		return User::class;
	}

	/**
	 * Which fields are queryable ?
	 *
	 * @return array
	 */
	public function getFetchable() {
		return [
			'id'          => Type::id(),
			'name'        => Type::string(),
			'last_login'  => Type::datetime(),
			'is_admin'    => Type::bool(),
			'permissions' => Type::json(),

			// Relationship between user and posts
			'posts'       => \GraphQL::listOf('post')
		];
	}

	/**
	 * Which fields are filterable ? And how ?
	 *
	 * @return array
	 */
	public function getFilterable() {
		return [
			'id'       => new EqualsOrContainsFilter(),
			"nameLike" => function($builder, $value) {
				return $builder->whereRaw('name like ?', $value),
			},
		];
	}

	/**
	 * Resolve field `permissions`
	 *
	 * @param  User $user
	 * @return array
	 */
	public function resolvePermissionsField(User $user) {
		return $user->getPermissions();
	}

	/**
	 * Which fields are mutable ?
	 *
	 * @return array
	 */
	public function getMutable() {
		return [
			'id'          => Type::id(),
			'name'        => Type::string(),
			'is_admin'    => Type::bool(),
			'permissions' => Type::array(),
			'password'    => Type::string()
		];
	}
}

# config/graphql.php

return [
	// ...
	'definitions' => [
		\App\GraphQL\Definition\UserDefinition::class,
		\App\GraphQL\Definition\PostDefinition::class
	],
	// ...
]
```

The definition is an essential part in the process. It defines queryable and
mutable fields. Also, it allows you to apply transformers for only some data
with the `getTransformers` methods. There's 5 kind of transformers to apply on :

* `list`  : create a query to fetch many objects (`User => users`)
* `view`  : create a query to retrieve one object (`User => user`)
* `drop`  : create a mutation to delete an object (`User => deleteUser`)
* `store` : create a mutation to update an object (`User => user`)
* `batch` : create a mutation to update many object at once (`User => users`)
* `restore` : create a mutation to restore an object (`User => restoreUser`)

By the default, the definition abstract class handles Eloquent model
transformation.

A definition is composed from types. Our custom class extend the default
`GraphQL\Type\Definition\Type` class in order to implement `json` and `datetime`
availabled types.

### Query

If you want create a query by hand, it's possible.

```php
# app/GraphQL/Query/Viewer.php

namespace App\GraphQL\Query;

use StudioNet\GraphQL\Support\Definition\Query;
use Illuminate\Support\Facades\Auth;

class Viewer extends Query {
	/**
	 * {@inheritDoc}
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}

	/**
	 * Return logged user
	 *
	 * @return \App\User|null
	 */
	public function getResolver() {
		return Auth::user();
	}
}

# config/graphql.php

return [
	'schema' => [
		'definitions' => [
			'default' => [
				'query' => [
					'viewer' => \App\GraphQL\Query\Viewer::class
				]
			]
		]
	],

	'definitions' => [
		\App\GraphQL\Definition\UserDefinition::class
	]
];
```

### Mutation

Mutation are used to update or create data.

```php
# app/GraphQL/Mutation/Profile.php

namespace App\GraphQL\Mutation;

use StudioNet\GraphQL\Support\Definition\Mutation;
use StudioNet\GraphQL\Definition\Type;
use App\User;

class Profile extends Mutation {
	/**
	 * {@inheritDoc}
	 *
	 * @return ObjectType
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getArguments() {
		return [
			'id'      => ['type' => Type::nonNull(Type::id())],
			'blocked' => ['type' => Type::string()]
		];
	};

	/**
	 * Update user
	 *
	 * @param  mixed $root
	 * @param  array $args
	 *
	 * @return User
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getResolver($root, array $args) {
		$user = User::findOrFail($args['id']);
		$user->update($args);

		return $user;
	}
}

# config/graphql.php

return [
	'schema' => [
		'definitions' => [
			'default' => [
				'query' => [
					'viewer' => \App\GraphQL\Query\Viewer::class
				],
				'mutation' => [
					'viewer' => \App\GraphQL\Mutation\Profile::class
				]
			]
		]
	],

	'definitions' => [
		\App\GraphQL\Definition\UserDefinition::class
	]
];
```

### Self documentation

A documentation generator is implemented with the package. By default, you can access it by navigate to `/doc/graphql`. You can change this behavior within the configuration file. The built-in documentation is implemented from [this repository](https://github.com/mhallin/graphql-docs).

### Examples

```graphql
query {
	viewer {
		name
		email

		posts {
			title
			content
		}
	}
}

# is equivalent to (if user id exists)

query {
	user (id: 1) {
		name
		email

		posts {
			title
			content
		}
	}
}
```

#### Using filters

When declaring the `getFilterable` array, you can define filters for fields.

You can either use a closure, or give class implementing FilterInterface.

The closure (or the `FilterInterface::updateBuilder` method) is then called
with:

* $builder : the current laravel query builder
* $value : the filter value
* $key : the filter key

You can use the predefined `EqualsOrContainsFilter` like below.

```php
	public function getFilterable() {
		return [
			// Simple equality check (or "in" if value is an array)
			'id'       => new EqualsOrContainsFilter(),
			// Customized filter
			"nameLike" => function($builder, $value) {
				return $builder->whereRaw('name like ?', $value);
			},
		];
	}
```


```graphql
query {
	users (take: 2, filter: {"id", "1"}) {
		id
		name
	}
}
```
This will execute a query : `WHERE id = 1`

```graphql
query {
	users (take: 2, filter: {"id", ["1,2"]}) {
		id
		name
	}
}
```
This will execute a query : `WHERE id in (1,2)`

```graphql
query {
	users (take: 2, filter: {"nameLike", "%santiago%"}) {
		id
		name
	}
}
```
This will execute a query : `WHERE name like '%santiago%'`

#### Ordering (`order_by`), limit (`take`), offset (`skip`)

You can specify the order of the results (which calls Eloquent's `orderBy`) with
the `order_by` argument (which is a `String[]`).

```graphql
query {
	users (order_by: ["name"]) { id, name }
}
```

You can specify a direction by appending `asc` (which is the default) or `desc`
to the order field :

```graphql
query {
	users (order_by: ["name_desc"]) { id, name }
}
```

You can specify multiple `order_by` :

```graphql
query {
	users (order_by: ["name_asc", "email_desc"]) { id, name }
}
```

You can limit the number of results with `take` (`Int`) :

```graphql
query {
	users (order_by: ["name"], take: 5) { id, name }
}
```

You can skip some results with `skip` (`Int`) :

```graphql
query {
	users (order_by: ["name"], take: 5, skip: 10) { id, name }
}
```



#### Mutation

```graphql
mutation {
	# Delete object
	delete : deleteUser(id: 5) {
		first_name
		last_name
	},

	# Update object
	update : user(id: 5, with : { first_name : "toto" }) {
		id
		first_name
		last_name
	},

	# Create object
	create : user(with : { first_name : "toto", last_name : "blabla" }) {
		id
		first_name
		last_name
	},

	# Update or create many objects at once
	batch  : users(objects: [{with: {first_name: 'studio'}}, {with: {first_name: 'net'}}]) {
		id
		first_name
	}
}
```

#### Mutation: custom input fields

You can specify a "mutable" field which is not in the Eloquent Model, and define
a custom method to it.

For a field named `foo_bar`, the method has to be named `inputFooBarField`, and
it has the Eloquent Model and the user input value as arguments.

Exemple (in `Definition`) :

```php
	use Illuminate\Database\Eloquent\Model;

	/* ... */

	public function getMutable() {
		return [
			'id' => Type::id(),
			'name' => Type::string(),
			// ...
			// Define a custom input field, which will uppercase the value
			'name_uppercase' => Type::string(),
		];
	}

	/* ... */

	/**
	 * Custom input field for name_uppercase
	 *
	 * @param Model $model
	 * @param string $value
	 */
	public function inputNameUppercaseField(Model $model, $value) {
		$model->name = mb_strtoupper($value);
	}
```

The input method is executed before the model is saved.

You can return an array with a "saved" callback, which will be executed
post-save (which can be useful for eloquent relational models) :

```php
	/**
	 * Custom input field for name_uppercase
	 *
	 * @param Model $model
	 * @param string $value
	 */
	public function inputNameUppercaseField(Model $model, $value) {
		$model->name = mb_strtoupper($value);

		return [
			'saved' => function() use ($model, $value) {
				// Executed after save
			}
		];
	}
```

## Contribution

If you want participate to the project, thank you ! In order to work properly,
you should install all dev dependencies and run the following commands before
pushing in order to prevent bad PR :

```bash
$> ./vendor/bin/phpmd src text phpmd.xml
$> ./vendor/bin/phpmd tests text phpmd.xml
$> ./vendor/bin/phpstan analyse --autoload-file=_ide_helper.php --level 1 src
$> ./vendor/bin/php-cs-fixer fix
```
