<?php
namespace StudioNet\GraphQL\Generator;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;
use StudioNet\GraphQL\Support\Eloquent\ModelAttributes;
use StudioNet\GraphQL\Generator\Query\Grammar;


/**
 * EloquentGenerator
 *
 * @see EloquentGeneratorInterface
 * @see Generator
 * @abstract
 */
abstract class EloquentGenerator extends Generator implements EloquentGeneratorInterface {
	/**
	 * {@inheritDoc}
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function getResolver(Model $model) {
		$attributes = $this->app->make(ModelAttributes::class);
		$relations  = $attributes->getRelations($model);

		return function($root, array $args, $context, ResolveInfo $info) use ($model, $relations) {
			$primary = $model->getKeyName();
			$builder = $model->newQuery();
			$fields  = $info->getFieldSelection(3);
			$common  = array_intersect_key($fields, $relations);

			if (!empty($common)) {
				foreach (array_keys($common) as $related) {
					$builder->with($related);
				}
			}
			// Retrieve single node
			if (array_key_exists('id', $args)) {
				return $builder->findOrFail($args['id']);
			}

			foreach ($args as $key => $value) {
				switch ($key) {
				case 'after'  : $builder = $builder->where($primary, '>', $value) ; break;
				case 'before' : $builder = $builder->where($primary, '<', $value) ; break;
				case 'skip'   : $builder = $builder->skip($value)                 ; break;
				case 'take'   : $builder = $builder->take($value)                 ; break;
				case 'filter' : $builder = $this->resolveFilter($builder, $value) ; break;
				}
			}
			return $builder->get();
		};
	}

	/**
	 * Resolve filter.
	 *
	 * @param  Builder $builder
	 * @param  array  $filter
	 * @return Builder
	 */
	private function resolveFilter($builder, array $filter) {
		return $this->getGrammar()->getBuilderForFilter($builder, $filter);
	}

	/**
	 * Return corresponding grammar from entity connection driver
	 *
	 * @return Grammar
	 */
	private function getGrammar() {
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
		return $grammar;
	}
}
