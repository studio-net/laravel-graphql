<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\Transformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Grammar;
use Illuminate\Database\Eloquent\Builder;
use GraphQL\Type\Definition\InputObjectType;
use StudioNet\GraphQL\Definition\Type;

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
	 * @return ListOf
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
		return [
			'after'  => [ 'type' => Type::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => [ 'type' => Type::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => [ 'type' => Type::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => [ 'type' => Type::int() , 'description' => 'Limit-based navigation'  ] ,
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
	 * @return array
	 */
	private function getFilterType(Definition $definition) {
		// TODO We have to manage this case
		$fields = array_filter($definition->getFetchable(), function($field) {
			return !is_array($field) or !array_key_exists('args', $field);
		});

		return new InputObjectType([
			'name'   => sprintf('%sFilter', ucfirst($definition->getName())),
			'fields' => array_map(function($field) {
				if (is_array($field)) {
					$field['type'] = Type::json();
				} else {
					$field = Type::json();
				}

				return $field;
			}, $fields)
		]);
	}

	/**
	 * Return fetchable node resolver
	 *
	 * @param  array $opts
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function getResolver(array $opts) {
		$builder = $opts['source']->newQuery()->with($opts['with']);

		// Parse each arguments in order to affect the query builder
		foreach ($opts['args'] as $key => $value) {
			switch ($key) {
			case 'after'  : $builder->where($primary, '>', $value)         ; break;
			case 'before' : $builder->where($primary, '<', $value)         ; break;
			case 'skip'   : $builder->skip($value)                         ; break;
			case 'take'   : $builder->take($value)                         ; break;
			case 'filter' : $this->resolveFilterArgument($builder, $value) ; break;
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
	private function resolveFilterArgument(Builder $builder, array $filter) {
		$driver  = \DB::connection()->getDriverName();
		$grammar = null;

		switch ($driver) {
			case 'pgsql' : $grammar = new Grammar\PostgreSQLGrammar ; break;
			case 'mysql' : $grammar = new Grammar\MySQLGrammar      ; break;
		}

		// Assert that grammar exists
		if (is_null($grammar)) {
			throw new \BadMethodCallException("{$driver} driver is not managed");
		}

		return $grammar->getBuilderForFilter($builder, $filter);
	}
}
