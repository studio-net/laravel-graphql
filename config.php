<?php
return [
	// Define GraphQL schema configuration
	//
	// `default`     : default schema to use
	// `definitions` : schemas definition
	//    - `{name}` : schema name
	//       - `query`    : list of custom queries
	//       - `mutation` : list of custom mutations
	'schema' => [
		'default'     => 'default',
		'definitions' => [
			'default' => [
				'query'    => [],
				'mutation' => []
			]
		]
	],

	// Type configuration. It allows you to define custom Type based on
	// StudioNet\GraphQL\Type\{EloquentType,Type}
	//
	// `entities`    : list of custom Type
	// `as_query`    : make each Type queriable
	// `as_mutation` : make each type mutable
	// `schemas`     : apply Type conversion to given schema.
	//    - `all` : all schemas
	//    - false : no schemas (equals to `as_query` and `as_mutation` = false)
	//    - [schema name]
	'type' => [
		'entities'    => [],
		'as_query'    => true,
		'as_mutation' => true,
		'schemas'     => 'all'
	],

	// Response configuration
	//
	// `headers` : custom headers to send on controller response
	// `json_encoding_options` : override default json_encode options
	'response' => [
		'headers' => [],
		'json_encoding_options' => 0,
	],

	// Route configuration to make GraphQL request
	//
	// `input_name` : default input name variable
	// `prefix`     : prefix URL to use for GraphQL endpoint (query and mutation)
	// `middleware` : middlewares to apply on routes group
	// `controller` : default controller to use
	//
	// You can easily extend the default controller by creating a custom class
	// which extend from original
	'route' => [
		'input_name' => 'variables',
		'prefix'     => 'graphql',
		'middleware' => [],
		'controller' => '\\StudioNet\\GraphQL\\GraphQLController@query'
	]
];
