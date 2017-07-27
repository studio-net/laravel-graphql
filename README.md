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
	'schema' => [
		'definitions' => [
			'default' => [
				'entities' => [\App\User::class]
			]
		]
	]
];
```

... Magic ! That's all you need to do.

### Query

Custom queries are not implemented yet.

### Mutation

Eloquent model-based mutations and custom mutation are not implemented yet.
