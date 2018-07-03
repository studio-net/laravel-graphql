<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Transformer\EloquentTransformer;
use StudioNet\GraphQL\Support\Definition\Definition;
use Illuminate\Database\Eloquent\Builder;
use GraphQL\Type\Definition\ObjectType;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into query listing
 *
 * @see EloquentTransformer
 */
class ListTransformer extends EloquentTransformer {
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
	 * {@inheritDoc}
	 * @return string
	 */
	public function getTransformerName(): string {
		return 'list';
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
	 * @param  Builder $builder
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	protected function then(Builder $builder, array $opts) {
		$query = $builder->getQuery();

		// Wrap pagination in a closure, so that it's evaluated only if requested
		$pagination = function () use ($builder, $query) {
			// https://github.com/laravel/framework/issues/5458
			$count = (clone $builder)->take(PHP_INT_MAX)->skip(0)->count();
			$paginate = [
				'totalCount' => $count,
				'page' => 0,
				'numPages' => 0,
				'hasNextPage' => false,
				'hasPreviousPage' => false,
			];

			if (!empty($query->limit)) {
				$paginate['page'] = ceil($query->offset / $query->limit);
				$paginate['numPages'] = ceil($count / $query->limit);
				$paginate['hasNextPage'] = $paginate['page'] < $paginate['numPages'] - 1;
				$paginate['hasPreviousPage'] = $paginate['page'] > 0;
			}

			return $paginate;
		};

		return [
			'items' => $builder->get(),
			'pagination' => $pagination,
		];
	}
}
