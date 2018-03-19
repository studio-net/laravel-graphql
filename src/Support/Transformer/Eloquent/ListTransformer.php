<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Grammar;
use Illuminate\Database\Eloquent\Builder;
use GraphQL\Type\Definition\InputObjectType;
use StudioNet\GraphQL\Definition\Type;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transform a Definition into query listing
 *
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
		return Type::listOf($definition->resolveType());
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
				break;
			case 'take':
				$builder->take($value);
				break;
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

		return $builder->get();
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
