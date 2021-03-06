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

If you're not using Laravel 5.5>=, don't forget to append facade and service
provider to you `config/app.php` file. Next, you have to publish vendor.

```bash
php artisan vendor:publish --provider="StudioNet\GraphQL\ServiceProvider"
```

## Usage

- [Definition](#definition)
- [Query](#query)
- [Mutation](#mutation)
- [Pipeline](#pipeline)
- [Require authorization](#require-authorization)
- [Self documentation](#self-documentation)
- [Examples](#examples)
- [N+1 Problem](#n1-problem)

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
use Auth;

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
use App\User;
use Auth;

class Viewer extends Query {
	/**
	 * {@inheritDoc}
	 */
	protected function authorize(array $args) {
		// check, that user is not a guest
		return !Auth::guest();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getSource() {
		return User::class;
	}

	/**
	 * Return logged user
	 *
	 * @return User|null
	 */
	public function getResolver($opts) {
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

`getResolver()` receives an array-argument with followed item:

- `root` 1st argument given by webonyx library - `GraphQL\Executor\Executor::resolveOrError()`
- `args` 2nd argument given by webonyx library
- `context` 3rd argument given by webonyx library
- `info` 4th argument given by webonyx library
- `fields` array of fields, that were fetched from query. Limited by depth in `StudioNet\GraphQL\GraphQL::FIELD_SELECTION_DEPTH`
- `with` array of relations, that could/should be eager loaded. **NOTICE:** Finding this relations happens ONLY, if `getSource()` is defined - this method should return a class name of a associated root-type in query. If `getSource()` is not defined, then `with` will be always empty.

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
	 */
	protected function authorize(array $args) {
		// check, that user is not a guest
		return !Auth::guest();
	}

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

### Pipeline

Pipeline are used to convert a definition into queryable and mutable operations.
But, you can easily create your own and manage useful cases like asserting ACL
before doing anything, etc.

Pipeline is implemented using the same [Laravel Middleware](https://laravel.com/docs/5.7/middleware) format
but pass as first argument the Eloquent Query Builder.

## Create new pipe

```php
namespace App/GraphQL/Pipe;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class OnlyAuthored {
	/**
	 * returns only posts that the viewer handle
	 *
	 * @param  Builder $builder
	 * @param  Closure $next
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function handle(Builder $builder, Closure $next, array $opts) {
		$builder->where('author_id', $opts['context']->getKey());

		return $next($builder);
	}
}
```

```php
namespace App\GraphQL\Definition;

class PostDefinition extends EloquentDefinition {
	// ...

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function getPipes(): array {
		return array_merge_recursive(parent::getPipes(), [
			'list' => [\App\GraphQL\Pipe\OnlyAuthored::class],
		]);
	}
	
	// ...
}
```

With this sample, when you'll query `posts` query, you'll only get viewer posts,
not all one. Also, you can specify arguments in the pipe, like following :

```php
namespace App/GraphQL/Pipe;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use GraphQL\Type\Definition\Type;
use StudioNet\GraphQL\Support\Pipe\Argumentable;
use StudioNet\GraphQL\Support\Definition\Definition;

class FilterableGroups implements Argumentable {
	/**
	 * returns only given groups
	 *
	 * @param  Builder $builder
	 * @param  Closure $next
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function handle(Builder $builder, Closure $next, array $opts) {
		if (array_get($opts, ['args.group_ids', false])) {
			$builder->whereIn('group_id', $opts['args']['group_ids']);
		}

		return $next($builder);
	}

	/**
	 * @implements
	 *
	 * @param  Definition $definition
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getArguments(Definition $definition): array {
		return [
			'groups_id' => [
				'type' => Type::json(),
				'description' => 'Filtering by group IDs'
			]
		];
	}
}
```

### Require authorization

Currently you have a possibility to protect your own queries and mutations. You have to implement `authorize()` method in your query/mutation, that return a boolean, that indicates, if requested query/mutation has to be executed. If method return `false`, an `UNAUTHORIZED` GraphQL-Error will be thrown.

Usage examples are in query and mutation above.

Protection of definition transformers are currently not implemented, but may be will in the future. By now you have to define your query/mutation yourself, and protect it then with logic in `authorize()`.

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

You can either use a closure, an array, or give object of class implementing FilterInterface.

The closure (or the `FilterInterface::updateBuilder` method) is then called
with:

* $builder : the current laravel query builder
* $value : the filter value
* $key : the filter key

You also may define graphql type for you filterable input field. By default `Type::json()` is used. There are several
options to define the type (all examples are listed in following code-block):

- if you are using class that implements `TypedFilterInterface`, returned type from method
`TypedFilterInterface::getType` is used;
- if you are using closure, you have to define an array with keys `type` containing type you wish and `resolver` 
containing closure;
- if you define an array, and in `resolver` is passed an object of class with implemented `TypedFilterInterface`, 
then type of `TypedFilterInterface::getType` will overwrite the type in an array key `type`;
- in all other situations `Type::json()` will be used as default type

You can also use the predefined `EqualsOrContainsFilter` like below.

```php
	public function getFilterable() {
		return [
			// Simple equality check (or "in" if value is an array). Type is Type::json()
			'id'       => new EqualsOrContainsFilter(),
			
			// Customized filter. Type is Type::json()
			"nameLike" => function($builder, $value) {
				return $builder->whereRaw('name like ?', $value);
			},
			
			// type is Type::string()
			"anotherFilter" => [
				"type" => Type::string(),
				"resolver" => function($builder, $value) {
					return $builder->whereRaw('anotherFilter like ?', $value);				
				}
			],
			
			// type is what is returned from `ComplexFilter::getType()`.
			// This is the preffered way to define filters, as it keeps definitions code clean
			"complexFilter" => new ComplexFilter(),
			
			// type in array will be overriden by what is returned from `ComplexFilter::getType()`.
			// this kind of difinition is not clear, but is implemented for backward compatibilities. Please don't use it
			"complexFilter2" => [
				"type" => Type::int(),
				"resolver" => new ComplexFilter()
			],
		];
	}
```


```graphql
query {
	users (take: 2, filter: {"id", "1"}) {
		items {
			id
			name
		}
	}
}
```
This will execute a query : `WHERE id = 1`

```graphql
query {
	users (take: 2, filter: {"id", ["1,2"]}) {
		items {
			id
			name
		}
	}
}
```
This will execute a query : `WHERE id in (1,2)`

```graphql
query {
	users (take: 2, filter: {"nameLike", "%santiago%"}) {
		items {
			id
			name
		}
	}
}
```
This will execute a query : `WHERE name like '%santiago%'`

#### Ordering (`order_by`)

You can specify the order of the results (which calls Eloquent's `orderBy`) with
the `order_by` argument (which is a `String[]`).

```graphql
query {
	users (order_by: ["name"]) { items { id, name } }
}
```

You can specify a direction by appending `asc` (which is the default) or `desc`
to the order field :

```graphql
query {
	users (order_by: ["name_desc"]) { items { id, name } }
}
```

You can specify multiple `order_by` :

```graphql
query {
	users (order_by: ["name_asc", "email_desc"]) { items { id, name } }
}
```

#### Pagination : limit (`take`), offset (`skip`)

You can limit the number of results with `take` (`Int`) :

```graphql
query {
	users (order_by: ["name"], take: 5) { items { id, name } }
}
```

You can skip some results with `skip` (`Int`) :

```graphql
query {
	users (order_by: ["name"], take: 5, skip: 10) { items { id, name } }
}
```

You can get useful pagination information :

```graphql
query {
	users (order_by: ["name"], take: 5, skip: 10) {
		pagination {
			totalCount
			page
			numPages
			hasNextPage
			hasPreviousPage
		}
		items {
			id
			name
		}
	}
}
```

Where :

* `totalCount` is the total number of results
* `page` is the current page (based on `take` which is used as the page size)
* `numPages` is the total number of pages
* `hasNextPage`, true if there is a next page
* `hasPreviousPage`, true if there is a previous page

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

### N+1 Problem

The common question is, if graphql library solves n+1 problem. This occures, when graphql resolves relation. Often entities are fetched without relations, and when graphql query needs to fetch relation, for each fetched entity relation would be fetched from SQL separately. So instead of executing 2 SQL queries, you will get N+1 queries, where N is the count of results of root entity. In that example you would query only one relation. If you query more relations, then it becomes N^2+1 problem.

To solve it, Eloquent has already options to eager load relations. Transformers in this library use eager loading, depends on what you query.

Currently this smart detection works perfect only on View and List Transformers. Other transformers will be reworked soon.

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
