Laravel GraphQL
===============

Use Facebook GraphQL with Laravel 5.2>=. It is based on the PHP
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

This is a work in progress.
> Warning : this package is not abled to run in production yet

## Installation

This package is only able to work with Laravel (at now).

### Laravel 5.2>=

Simply run

```bash
composer require 'studio-net/laravel-graphql:dev-master'
```

... and add service provider to `config/app.php` :

```php
<?php
return [
	// ...

	'providers' => [
		// ...
		StudioNet\GraphQL\ServiceProvider::class
		// ...
	],

	'aliases'   => [
		// ...
		'GraphQL' => StudioNet\GraphQL\Support\Facades\GraphQL::class
		// ...
	]
];
```

Now, you can run the following command and review the `config/graphql.php` file

```bash
$ php artisan vendor:publish --provider="StudioNet\GraphQL\ServiceProvider"
```

## Usage

- [Transformer](#transformer)
- [Type](#type)
- [Generator](#generator)
- [Query](#query)
- [Mutation](#mutation)
- [Self documentation](#self-documentation)
- [Example](#example)

### Transformer

In order to make you understand how this package works, we have to start with
transformer. A transformer is a simple way to transform any kind of source-data
into an `ObjectType`. By default, we've implemented two transformers :
`ModelTransformer` and `TypeTransformer`.

* `ModelTransformer` transforms an Eloquent model by using reflection from
database field, hiddens and fillable fields declared within the class. It also
supports relationships between models ;
* `TypeTransformer` transforms a custom built class inherit from
`StudioNet\GraphQL\Support\Type` class ;

Each transformer must have a `supports` method. If a transformer can handle a
given type of data, it will be converted. Otherwise, an exception will be
thrown. All transformers are registered within the configuration file. If you
want create you're custom one, just append it.

### Type

You can register any source of data that you want into the configuration file
(if a transformer can handle it).

```php
# app/User.php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * User
 *
 * @see Model
 */
class User extends Model {
	use Notifiable;

	/** @var array $hidden */
	protected $hidden = ['password'];
}

# config/graphql.php

return [
    'type' => [
        \App\User::class,
    ]
]
```

You can use two kind of syntax : the first one is shown above. The second one
using alias like `'user' => \App\User::class`.

#### Overide default name and description

Each model can implements `StudioNet\GraphQL\Support\Interfaces\ModelAttributes`
interface in order to override default name and descriptions. By default,
they're built based on generic sentence and singularize table name.

#### `StudioNet\GraphQL\Transformer\Type\ModelTransformer`

Transform given `Illuminate\Database\Eloquent\Model` to `EloquentObjectType`. It
lists all availabled fetchable columns and resolve each relationships fields
with following configuration (`hasMany`) :

```
- Type      : List of "EloquentObjectType"
- Return    : "Illuminate\Support\Collection"
- Arguments :
  - "take"   : limit
  - "skip"   : offset
  - "before" : cursor-based navigation
  - "after"  : cursor-based navigation
```

I case of relationships and if `StudioNet\GraphQL\Support\Type\Meta` type is
registered, you'll be granted to use field like `_posts_meta { count }` in order
to retrieve global count.

You could also override default name and description for each entity by
implementing `getObjectDescription` and `getObjectName` within each of them.
Methods must return a string.

This transformer also converts [mutated fields from model](https://laravel.com/docs/5.4/eloquent-mutators).
Let's show you the convertion mapping (based on supported model cast) :

```
+------------------+--------+---------+------------+---------+
| integer          | float  | boolean | array      | string  |
+------------------+--------+---------+------------+---------+
| real             | double | boolean | object     | default |
| int              | float  |         | array      |         |
| integer          |        |         | collection |         |
| date (epoch)     |        |         |            |         |
| datetime (epoch) |        |         |            |         |
+------------------+--------+---------+------------+---------+
```

#### `StudioNet\GraphQL\Transformer\TypeTransformer`

Transform user-specified type. It handles the following methods :

```
- getName()        : Return type name
- getDescription() : Return type description
- getAttributes()  : Return type attributes
- getFields()      : Return type fields
- getInterfaces()  : Return type interfaces
```

### Generator

Generators are used to make life easier : for example, it's very useful when a
`EloquentObjectType` is passed throw : it can generate a singular and a
pluralize query for us. A generator is similar to a transformer : it handles a
`supports` methods but return an array (it doesn't convert any data, just use
existing one).

```php
# config/graphql.php

return [
	'generator' => [
		'query'    => [
			\StudioNet\GraphQL\Generator\Query\NodeEloquentGenerator::class,
			\StudioNet\GraphQL\Generator\Query\NodesEloquentGenerator::class
		],
		'mutation' => [
			\StudioNet\GraphQL\Generator\Mutation\NodeEloquentGenerator::class,
		]
	]
]
```

When using `EloquentGenerator`, you can create a model method called
`resolveQuery` in order to customize the resolve GraphQL method. In order to
make them all sharing the same custom resolver, we suggest you to create a
`Trait`.

```php
use Illuminate\Database\Eloquent\Builder;

/**
 * GraphQLResolver
 */
trait GraphQLResolver {
	/**
	 * Custom GraphQL resolver
	 *
	 * @param  Builder $builder
	 * @param  array $args
	 * @return Builder
	 */
	public function resolveQuery(Builder $builder, array $args) {
		// ...
		// You cannot resolve twice following keywords because they already used
		//
		// `id`, `after`, `before`, `skip`, `take` and `filter`
	}
}
```

#### `StudioNet\GraphQL\Generator\Query\NodeEloquentGenerator`

Generate singular query based on `EloquentObjectType`.

```
- Type      : "EloquentObjectType"
- Return    : "Illuminate\Database\Eloquent\Model"
- Arguments :
  - "id" : id-based navigation
```

#### `StudioNet\GraphQL\Generator\Query\MetaEloquentGenerator`

Generate meta query based on `EloquentObjectType`.

```
- Type      : "EloquentObjectType"
- Return    : [
   - "count" : count of objects
]
```

#### `StudioNet\GraphQL\Generator\Query\NodesEloquentGenerator`

Generate pluralized query based on `EloquentObjectType`.

```
- Type      : List of "EloquentObjectType"
- Return    : "Illuminate\Database\Eloquent\Collection"
- Arguments :
  - "take"   : limit
  - "skip"   : offset
  - "before" : cursor-based navigation
  - "after"  : cursor-based navigation
  - "filter" : filter-based query (see examples)
```

#### `StudioNet\GraphQL\Generator\Mutation\NodeEloquentGenerator`

Generate mutation based on `EloquentObjectType`. It allows create or update
methods (primary key specified).

```
- Type      : "EloquentObjectType"
- Return    : "Illuminate\Database\Eloquent\Model"
- Arguments :
  - "{primaryKey}" : model defined primary key
  - "{columns}"    : availabled fillable or non-guarded fields (no hidden ones)
```

#### `StudioNet\GraphQL\Generator\Mutation\QueryEloquentGenerator`

Generate mutation based on `EloquentObjectType`. It allows delete an entity
based on the primary key.

```
- Type      : "EloquentObjectType"
- Return    : "Illuminate\Database\Eloquent\Model"
- Arguments :
  - "id" : model defined primary key
```

### Query

Query are used in order to fetch data. Each query has it's own related
[type](#type).

```php
# app/GraphQL/Query/Viewer.php

namespace App\GraphQL\Query;

use StudioNet\GraphQL\Support\Query;
use App\User;

class Viewer extends Query {
	/**
	 * {@inheritDoc}
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}

	/**
	 * Return first user in database
	 *
	 * @return \App\User
	 */
	public function resolve() {
		return User::first();
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

	'type' => [
		\App\User::class
	]
];
```

### Mutation

Mutation are used to update or create data :

```php
# app/GraphQL/Mutation/Profile.php

namespace App\GraphQL\Mutation;

use StudioNet\GraphQL\Support\Mutation;
use GraphQL\Type\Definition\Type as GraphQLType;
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
			'id'      => ['type' => GraphQLType::nonNull(GraphQLType::id())],
			'blocked' => ['type' => GraphQLType::string()]
		];
	};

	/**
	 * Return current logged user
	 *
	 * @param  mixed $root
	 * @param  array $args
	 *
	 * @return \App\User
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function resolve($root, array $args) {
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

	'type' => [
		\App\User::class
	]
];
```

Mutation's aliases are not dependent of query's one

### Self documentation

A documentation generator is implemented with the package. By default, you can access it by navigate to `/doc/graphql`. You can change this behavior within the configuration file. The built-in documentation is implemented from [this repository](https://github.com/mhallin/graphql-docs).

### Example

To run the following example, we assumed that you have two existing models :
`User` and `Post`. A `User` model has a method `posts` that will return a
`HasMany` relationship with `Post` model.

We'll simply create a query that represents the first user in the database
(there's no really use case but it's only for the example). We just have to
register models and created query.

```php
# app/GraphQL/Query/Viewer.php

namespace App\GraphQL\Query;

use GraphQL;
use StudioNet\GraphQL\Support\Query;

/**
 * Viewer
 *
 * @see Query
 */
class Viewer extends Query {
	/**
	 * {@inheritDoc}
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}

	/**
	 * Return first user in database
	 *
	 * @return \App\User
	 */
	public function getResolver() {
		return \App\User::first();
	}
}

# config/graphql.php

return [
	'schema' => [
		'default' => 'default',
		'definitions' => [
			'default' => [
				'query'    => [\App\GraphQL\Query\Viewer::class],
				'mutation' => []
			]
		]
	],

	'type' => [\App\User::class, \App\Post::class]
];
```

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

```graphql
query {
	users (take: 2, filter:{
			'first_name':'%Targaryen',
			'id': '(gt) 5'
		})
	{
		id
		first_name
		last_name

		posts (take: 5) {
			id
			title
			content
		}
	}
}
```

#### Using metadata

```php
# config/graphql.php

return [
	'type' => [
		\StudioNet\GraphQL\Support\Type\Meta::class,
		\App\User::class,
		\App\Post::class
	]
];
```

```graphql
query {
	_users_meta {
		count
	}

	users (take: 2) {
		id
		first_name
		last_name

		posts (take: 5) {
			id
			title
			content
		}

		_posts_meta {
			count
		}
	}
}
```

#### Mutation

```graphql
mutation {
	delete : deleteUser(id: 5) {
		first_name
		last_name
	},

	update : user(id: 5, first_name : "toto") {
		id
		first_name
		last_name
	},

	create : user(first_name : "toto", last_name : "blabla") {
		id
		first_name
		last_name
	}
}
```
