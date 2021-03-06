<?php
namespace StudioNet\GraphQL\Support\Pipe\Eloquent;

use Closure;
use GraphQL\Type\Definition\InputObjectType;
use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Filter\FilterInterface;
use StudioNet\GraphQL\Filter\TypedFilterInterface;
use StudioNet\GraphQL\Grammar;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Support\Pipe\Argumentable;

/**
 * FilterPipe
 *
 * @see Argumentable
 */
class FilterPipe implements Argumentable {
	/**
	 * handle
	 *
	 * @param  Builder $builder
	 * @param  Closure $next
	 * @param  array $opts
	 * @return void
	 */
	public function handle(Builder $builder, Closure $next, array $opts) {
		if (array_key_exists('filter', $opts['args'])) {
			$driver = \DB::connection()->getDriverName();
			$grammar = null;

			switch ($driver) {
				case 'pgsql': $grammar = new Grammar\PostgreSQLGrammar ; break;
				case 'mysql': $grammar = new Grammar\MySQLGrammar      ; break;
				case 'sqlite': $grammar = new Grammar\SqliteGrammar      ; break;
			}

			// Assert that grammar exists
			if (is_null($grammar)) {
				throw new \BadMethodCallException("{$driver} driver is not managed");
			}

			$grammar->getBuilderForFilter($builder, $opts['args']['filter'], $opts['filterables']);
		}

		return $next($builder);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getArguments(Definition $definition): array {
		return ['filter' => ['type' => $this->getType($definition), 'description' => 'Performs filtering']];
	}

	/**
	 * Returns `filter` argument type object
	 *
	 * @param  Definition $definition
	 * @return InputObjectType
	 */
	private function getType(Definition $definition) {
		$queryable = [];

		foreach ($definition->getFilterable() as $field => $filter) {
			// define default values
			$fieldType = Type::json();

			// if we got instance of TypedFilterInterface, extract type
			if ($filter instanceof TypedFilterInterface) {
				$fieldType = $filter->getType();
			}

			// if we got an array, try to fetch type and resolver Function/FilterInterface
			if (is_array($filter)) {
				if (isset($filter['resolver']) && $filter['resolver'] instanceof TypedFilterInterface) {
					$fieldType = $filter['resolver']->getType();
				} else {
					$fieldType = array_get($filter, 'type', Type::json());
				}
			}

			$queryable[$field] = $fieldType;
		}

		return new InputObjectType([
			'name' => ucfirst($definition->getName()) . 'Filter',
			'fields' => $queryable
		]);
	}
}
