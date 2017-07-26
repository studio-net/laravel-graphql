<?php
return [
	'default_schema' => 'default',
	'types'   => [],
	'schemas' => [
		'default' => [
			'query'    => [],
			'mutation' => []
		]
	],

	'types'            => [],
	'types_as_query'   => true,
	'types_in_schemas' => 'all', // 'all', 'none', [schema name]

	'headers' => [],
	'json_encoding_options' => 0,
	'variable_input_name' => 'variables',

	'route' => [
		'prefix'     => 'graphql',
		'middleware' => [],
		'controller' => '\\StudioNet\\GraphQL\\GraphQLController@query'
	]
];
