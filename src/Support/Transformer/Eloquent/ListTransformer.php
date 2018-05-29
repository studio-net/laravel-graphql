<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Grammar;
use Illuminate\Database\Eloquent\Builder;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use StudioNet\GraphQL\Definition\Type;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transform a Definition into query listing
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @see Transformer
 */
class ListTransformer extends Transformer {
	/**
	 * Return query name
	 *
	 * @param  Definition $definition
	 * @return string
	 */
	public function getName(Definition $definition) {
		return strtolower(str_plural($definition->getName()));
	}

	/**
	 * {@overide}
	 *
	 * @param  Definition $definition
	 * @return \GraphQL\Type\Definition\ListOfType
	 */
	public function resolveType(Definition $definition) {
		return new ObjectType([
			'name' => $this->getName($definition) . 'Items',
			'description' => "Items",
			'fields' => [
				'items' => Type::listOf($definition->resolveType()),
				'pagination' => Type::pagination(),
			],
		]);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition) {
		$args = [];
		$traits = class_uses($definition->getSource());

		// If the source uses soft deletion, we have to append one arg to fetch
		// hidden ones and other to only fetch hidden
		if (in_array(SoftDeletes::class, $traits)) {
			$args = [
				'trashed' => ['type' => Type::bool(), 'description' => 'Show deleted'],
				'only_trashed' => ['type' => Type::bool(), 'description' => 'Show only deleted'],
			];
		}

		return $args + [
			'after' => [ 'type' => Type::id()  , 'description' => 'Cursor-based navigation' ] ,
			'before' => [ 'type' => Type::id()  , 'description' => 'Cursor-based navigation' ] ,
			'skip' => [ 'type' => Type::int() , 'description' => 'Offset-based navigation' ] ,
			'take' => [ 'type' => Type::int() , 'description' => 'Limit-based navigation'  ] ,
			'filter' => [
				'type' => $this->getFilterType($definition),
				'description' => 'Performs search'
			],
			'order_by' => [
				'type' => Type::listOf(Type::string()),
				'description' => 'Ordering results',
			]
		];
	}

	/**
	 * Return filter type object
	 *
	 * @param  Definition $definition
	 * @return InputObjectType
	 */
	private function getFilterType(Definition $definition) {
		$queryableFields = [];
		foreach ($definition->getFilterable() as $field => $filter) {
			$queryableFields[$field] = [
				"type" => Type::json(),
				"filter" => $filter,
			];
		}

		return new InputObjectType([
			'name' => ucfirst($definition->getName()) . 'Filter',
			'fields' => $queryableFields,
		]);
	}

	/**
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getResolver(array $opts) {
		$builder = $opts['source']->newQuery()->with($opts['with']);
		$primary = $opts['source']->getKeyName();

		// Parse each arguments in order to affect the query builder
		// (Before count)
		foreach ($opts['args'] as $key => $value) {
			switch ($key) {
			case 'trashed':
				$builder->withTrashed();
				break;
			case 'only_trashed':
				$builder->onlyTrashed();
				break;
			case 'filter':
				$this->resolveFilterArgument($builder, $value, $opts['filterables']);
				break;
			}
		}

		// Save the builder before adding pagination arguments
		$countBuilder = clone($builder);

		$take = null;
		$skip = null;

		// Parse each arguments in order to affect the query builder (paginate)
		foreach ($opts['args'] as $key => $value) {
			switch ($key) {
			case 'after':
				$builder->where($primary, '>', $value);
				break;
			case 'before':
				$builder->where($primary, '<', $value);
				break;
			case 'skip':
				$builder->skip($value);
				$skip = $value;
				break;
			case 'take':
				$builder->take($value);
				$take = $value;
				break;
			case 'order_by':
				$this->applyOrderBy($builder, $value);
				break;
			}
		}


		// Wrap pagination in a closure, so that it's evaluated only if requested
		$pagination = function () use ($countBuilder, $skip, $take) {
			$totalCount = $countBuilder->count();
			$res = [
				'totalCount' => $totalCount,
				'page' => 0,
				'numPages' => 0,
				'hasNextPage' => false,
				'hasPreviousPage' => false,
			];
			if (!empty($take)) {
				$res['page'] = ceil($skip / $take);
				$res['numPages'] = ceil($totalCount / $take);
				$res['hasNextPage'] = $res['page'] < $res['numPages'] - 1;
				$res['hasPreviousPage'] = $res['page'] > 0;
			}
			return $res;
		};

		return [
			'items' => $builder->get(),
			'pagination' => $pagination,
		];
	}

	/**
	 * Apply the order_by argument
	 *
	 * @param Builder $builder
	 * @param mixed $value
	 */
	private function applyOrderBy(Builder $builder, $values) {
		foreach ($values as $orderToken) {
			$order = $orderToken;
			$direction = 'ASC';
			if (preg_match('/^(.*)_(asc|desc)$/i', $orderToken, $matches)) {
				$order = $matches[1];
				$direction = strtoupper($matches[2]);
			}
			$builder->orderBy($order, $direction);
		}
	}

	/**
	 * Resolve filter argument
	 *
	 * @param  Builder $builder
	 * @param  array $filter
	 * @return Builder
	 */
	private function resolveFilterArgument(
		Builder $builder,
		array $filter,
		array $filterables
	) {
		$driver = \DB::connection()->getDriverName();
		$grammar = null;

		switch ($driver) {
			case 'pgsql': $grammar = new Grammar\PostgreSQLGrammar ; break;
			case 'mysql': $grammar = new Grammar\MySQLGrammar      ; break;
			case 'sqlite': $grammar = new Grammar\SqliteGrammar    ; break;
		}

		// Assert that grammar exists
		if (is_null($grammar)) {
			throw new \BadMethodCallException("{$driver} driver is not managed");
		}

		return $grammar->getBuilderForFilter($builder, $filter, $filterables);
	}
}
