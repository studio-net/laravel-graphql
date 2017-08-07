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
			]
		]
	],

	// Type configuration. You can append any data : a transformer will handle
	// them (if exists)
	'type' => [],

	// Scalar field definitions
	'scalar' => [
		\StudioNet\GraphQL\Support\Scalar\Timestamp::class,
		\StudioNet\GraphQL\Support\Scalar\JsonArray::class
	],

	// A transformer handles a supports and a transform method. I can convert
	// any type of data in specific content. In order to make work Eloquent
	// models, a transformer convert it into specific ObjectType.
	//
	// Take care about order : the first supported transformer will handle the
	// transformation ; others will simply not be called. I you want make
	// modifications about a specific transformer, you'll have to extend
	// existing one and replace it below
	//
	// There's 3 types of transformers : type, query and mutation
	'transformer' => [
		'type'     => [
			\StudioNet\GraphQL\Transformer\Type\ModelTransformer::class,
			\StudioNet\GraphQL\Transformer\TypeTransformer::class,
		],
		'query'    => [
			\StudioNet\GraphQL\Transformer\FieldTransformer::class,
		],
		'mutation' => [
			\StudioNet\GraphQL\Transformer\FieldTransformer::class,
		]
	],

	// A generator can be applied with each ObjectType. You can register any
	// generator you want.
	//
	// Instead of transformers, generators will not stopping at first match :
	// all will be called
	'generator' => [
		'query'    => [
			\StudioNet\GraphQL\Generator\Query\NodeEloquentGenerator::class,
			\StudioNet\GraphQL\Generator\Query\NodesEloquentGenerator::class
		],
		'mutation' => [
			\StudioNet\GraphQL\Generator\Mutation\NodeEloquentGenerator::class,
		]
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
	],

	// Route in order to auto-generate documentation for GraphQL schema
	//
	// `prefix` : route prefix
	// `route`  : full route (without prefix)
	//
	// Will generate URL like `prefix`/`route`
	'documentation' => [
		'active' => true,
		'prefix' => 'doc',
		'route'  => 'graphql/{schema?}'
	]
];
