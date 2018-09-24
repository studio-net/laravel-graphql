<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use StudioNet\GraphQL\Support\Transformer\EloquentTransformer;
use StudioNet\GraphQL\Support\Transformer\Paginable;
use StudioNet\GraphQL\Support\Definition\Definition;
use Illuminate\Database\Eloquent\Builder;
use GraphQL\Type\Definition\ObjectType;
use StudioNet\GraphQL\Definition\Type;

/**
 * Transform a Definition into query listing
 *
 * @see EloquentTransformer
 */
class ListTransformer extends EloquentTransformer implements Paginable {
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
	 * {@inheritDoc}
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getArguments(Definition $definition): array {
		$args = parent::getArguments($definition);
		$traits = class_uses($definition->getSource());

		if (in_array(SoftDeletes::class, $traits)) {
			$args = $args + [
				'only_trashed' => ['type' => Type::bool(), 'description' => 'Show only deleted'],
				'trashed' => ['type' => Type::bool(), 'description' => 'Show deleted'],
			];
		}

		return $args;
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
		$builder = $this->manageBuilderArguments($builder, $opts['args']);
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

	/**
	 * manageBuilderArguments
	 *
	 * @param  Builder $builder
	 * @param  array $opts
	 * @return Builder
	 */
	protected function manageBuilderArguments(Builder $builder, array $args) {
		if (array_key_exists('only_trashed', $args)) {
			$builder->onlyTrashed();
		}

		if (array_key_exists('trashed', $args)) {
			$builder->withTrashed();
		}

		return $builder;
	}
}
