Laravel GraphQL
===============

Use Facebook GraphQL with Laravel 5 or Lumen. It is based on the PHP implementation [here](https://github.com/webonyx/graphql-php). You can find more information about GraphQL in the [GraphQL Introduction](http://facebook.github.io/react/blog/2015/05/01/graphql-introduction.html) on the [React](http://facebook.github.io/react) blog or you can read the [GraphQL specifications](https://facebook.github.io/graphql/). This is a work in progress.

> Warning : this package is not abled to run in production yet

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

## Usage

- [Basic usage](#basic-usage)
- [Query](#query)
- [Mutation](#mutation)

### Basic usage

If you're using Laravel with Eloquent and wanna implement GraphQL easily, you're
very lucky. You can quickly do that with the following example :

```php
# app/graphql.php

return [
	'type' => [
		'entities' => [\App\User::class, \App\Post::class]
	]
];
```

... Magic ! That's all you need to do.

#### More explanations

In fact, each entity has some attributes like hidden fields and can also have
some relations with anothers entities. You're models are re-used in order to
keep your desire. So, let's take the following entities example :

```
\App\User :
    - fields [name, password, email]
    - hidden fields [password]
    - posts [hasMany \App\Post]
   
\App\Post :
    - fields [title, content, user_id]
    - author [belongsTo \App\User]
```

You will be able to make queries like :

```graphql
query {
	users(take: 10) {
		name
			email

			posts(take: 2, skip : 1) { # Availabled filters : take, skip, after, before
				title
				content

				author { # Don't really smart, but it's okay
					name

					# posts { # Will not work because reached depth
					#      title
					# }
				}
			}
	}
}

query {
	user(id: 1) { # Availabled filters : id
		name
			email

			posts {
				title
					content
			}
	}
}
```

By default, each entity are created as type. You can call them within each
custom query with the facade like `\GraphQL::type('user')` or
`\GraphQL::listOf('user')`. Entity name is built from lowercase class name.

### Query

Queries are auto-generated based on each model. You can fill a custom name when
register or use the default one.

#### Custom query

You can implement any custom query like the following example :

```php
# app/GraphQL/Query/Viewer.php
namespace App\GraphQL\Query;

use StudioNet\GraphQL\Support\Query;

class Viewer extends Query {
	/**
	 * {@inheritDoc}
	 *
	 * @return ObjectType
	 */
	public function getRelatedType() {
		return \GraphQL::type('user');
	}

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
		return \App\User::first();
	}
}

# config/graphql.php
return [
	'schema' => [
		'definitions' => [
			'default' => [
				'query' => [
					\App\GraphQL\Query\Viewer::class
					// 'alias' => \App\GraphQL\Query\Viewer::class
				]
			]
		]
	],

	'type' => [
		'entities' => [\App\User::class]
	]
];
```

By default, if you don't specify alias for each query, the class name will be
used (as lowercase) : `\App\GraphQL\Query\Viewer => viewer`. Of course, you can
use custom type within each of your custom queries (you're not constraint to use
entities's one).

### Mutation

As query, mutations are auto-generated based on each specific model. You can
update each entity based on the name you've filled or with default one.

```graphql
mutation {
	updateUser : user(id: 1, name = 'Jean Dupont') {
		id
		name
	}
}
```

#### Custom mutation

You can create custom mutation if you want, like the following example :

```php
# app/GraphQL/Mutation/Profile.php
namespace App\GraphQL\Mutation;

use StudioNet\GraphQL\Support\Mutation;

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
		$user = \App\User::findOrFail($args['id']);
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
					\App\GraphQL\Query\Viewer::class
					// 'alias' => \App\GraphQL\Query\Viewer::class
				],
				'mutation' => [
					\App\GraphQL\Mutation\Profile::class
				]
			]
		]
	],

	'type' => [
		'entities' => [\App\User::class]
	]
];
```
