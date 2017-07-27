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
				'mutation' => [],
				'entities' => []
			]
		]
	],

	// Type configuration. It allows you to define custom Type based on
	// StudioNet\GraphQL\Type\{EloquentType,Type}
	//
	// `entities`    : list of entities
	// `definitions` : list of custom Type
	'type' => [
		'entities'    => [],
		'definitions' => []
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
