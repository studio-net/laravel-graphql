Laravel GraphQL
===============

Use Facebook GraphQL with Laravel 5 or Lumen. It is based on the PHP
implementation [here](https://github.com/webonyx/graphql-php). You can find more
information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html)
on the [React](http://facebook.github.io/react) blog or you can read the
[GraphQL specifications](https://facebook.github.io/graphql/).

This is a work in progress.
> Warning : this package is not abled to run in production yet

## Installation

This package is only able to work with Laravel (at now). Later, a Lumen service
provider will be added.

### Laravel 5.x

Add service provider to `config/app.php` :

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

### Lumen 5.x

(not impleted yet)

## Usage

- [Transformer](#transformer)
- [Type](#type)
- [Generator](#generator)
- [Query](#query)
- [Mutation](#mutation)
- [Self documentation](#self-documentation)

### Transformer

In order to make you understand how this package works, we have to start with
transformer. A transformer is a simple way to transform any kind of source-data
into an `ObjectType`. By default, we've implemented two transformers :
`ModelTransformer` and `TypeTransformer`.

* `ModelTransformer` transforms an Eloquent model by using reflection from
database field, hiddens and fillable fields declared within the class. It also
supports relationships between models ; This transformer has a dependency on
`StudioNet\GraphQL\Traits\EloquentModel` trait : you have to use it in either
eloquent model you registered ;
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
use StudioNet\GraphQL\Traits\EloquentModel;
use Illuminate\Notifications\Notifiable;

/**
 * User
 *
 * @see Model
 */
class User extends Model {
	use EloquentModel; // -> Mandatory
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

### Generator

Generators are used to make life easier : for example, it's very useful when a
`EloquentObjectType` is passed throw : it can generate a singular and a
pluralize query for us. A generator is similar to a transformer : it handles a
`supports` methods but return an array (it doesn't convert any data, just use
existing one).

```
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
