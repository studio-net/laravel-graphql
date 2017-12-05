<?php
namespace StudioNet\GraphQL;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use GraphQL\Utils;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

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
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function query(Request $request, $schema = null) {
		$inputs = $request->all();
		$data   = [];

		// If there's no schema, just use default one
		if (empty($schema)) {
			$schema = config('graphql.schema.default', 'default');
		}

		// Execute statements in transaction in order to prevent error during
		// creation, update or drop
		DB::beginTransaction();

		try {
			// If we're working on batch queries, we have to parse and execute each
			// of them separatly
			if (array_keys($inputs) === range(0, count($inputs) - 1)) {
				foreach ($inputs as $input) {
					$data[] = $this->executeQuery($schema, $input);
				}
			}

			// Otherwise, we just have to handle given query
			else {
				$data = $this->executeQuery($schema, $inputs);
			}

			// If everything is okay, just commit the transaction
			DB::commit();
		} catch (\Exception $exception) {
			$data['error'] = $exception->getMessage();

			// Rollback transaction is any error occurred
			DB::rollBack();
			Log::debug($exception);
		}

		$headers = config('graphql.response.headers', []);
		$options = config('graphql.response.json_encoding_options', 0);
		$status  = (array_key_exists('errors', $data)) ? 500 : 200;

		return Response::json($data, $status, $headers, $options);
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
