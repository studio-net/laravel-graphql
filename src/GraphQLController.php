<?php
namespace StudioNet\GraphQL;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

/**
 * GraphQLController
 *
 * @see Controller
 */
class GraphQLController extends Controller {
	/**
	 * Execute query and return statement
	 *
	 * @param  Request $request
	 * @param  null|string $schema
	 *
	 * @return JsonResponse
	 */
	public function query(Request $request, $schema = null) {
		$inputs = $request->all();
	
		// If there's no schema, just use default one
		if (empty($schema)) {
			$schema = config('graphql.schema.default');
		}

		// If we're working on batch queries, we have to parse and execute each
		// of them separatly
		if (array_keys($inputs) === range(0, count($inputs) - 1)) {
			$data = [];

			foreach ($inputs as $input) {
				$data[] = $this->executeQuery($schema, $input);
			}
		}

		// Otherwise, we just have to handle given query
		else {
			$data = $this->executeQuery($schema, $inputs);
		}

		$headers = config('graphql.response.headers', []);
		$options = config('graphql.response.json_encoding_options', 0);

		return response()->json($data, 200, $headers, $options);
	}

	/**
	 * Execute given query
	 *
	 * @param  string $schema
	 * @param  array $inputs
	 *
	 * @return array
	 */
	private function executeQuery($schema, array $inputs) {
		$query = array_get($inputs, 'query');
		$name  = array_get($inputs, 'operationName');
		$args  = array_get($inputs, config('graphql.route.input_name', 'variables'));

		if (is_string($args)) {
			$args = json_decode($args, true);
		}

		return app(GraphQL::class)->execute($query, $args, [
			'context' => $this->getContext(),
			'schema'  => $schema,
			'operationName' => $name
		]);
	}

	/**
	 * Return availabled context
	 *
	 * @return mixed
	 */
	protected function getContext() {
		try {
			return app('auth')->user();
		} catch (\Exception $e) {}

		return null;
	}
}
