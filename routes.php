<?php
$parameters = [
	'prefix'     => config('graphql.route.prefix'),
	'middleware' => config('graphql.route.middleware', [])
];

Route::group($parameters, function() {
	$controller = config('graphql.route.controller', '\\StudioNet\\GraphQL\\GraphQLController@query');

	Route::get('/', ['as' => 'graphql.query', 'uses' => $controller]);
	Route::post('/', ['as' => 'graphql.query.post', 'uses' => $controller]);
});
