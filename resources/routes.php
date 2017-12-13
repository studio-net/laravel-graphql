<?php
// #############################################################################
// GraphQL routes definition
// #############################################################################

$parameters = [
	'prefix' => config('graphql.route.prefix'),
	'middleware' => config('graphql.route.middleware', [])
];

Route::group($parameters, function () {
	$controller = config('graphql.route.controller', '\\StudioNet\\GraphQL\\GraphQLController@query');

	Route::get('/{schema?}', ['as' => 'graphql.query'      , 'uses' => $controller]);
	Route::post('/{schema?}', ['as' => 'graphql.query.post' , 'uses' => $controller]);
});

// #############################################################################
// Self-generated documentation
// #############################################################################

if (config('graphql.documentation.active', true)) {
	$prefix = config('graphql.documentation.prefix', 'doc');

	Route::group(['prefix' => $prefix], function () {
		$route = config('graphql.documentation.route', 'graphql');

		Route::get($route, function () {
			return view("graphql::documentation/graphql");
		});
	});
}
