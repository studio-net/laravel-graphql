<?php
namespace StudioNet\GraphQL\Support\Transformer\Eloquent;

use StudioNet\GraphQL\Support\Pipe\Pipeline;
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
	 * Returns Pipeline resolved Builder
	 *
	 * @param  array $opts
	 * @return void
	 */
	public function getResolver(array $opts) {
		return (new Pipeline($this->app))
			->send($opts['source']->newQuery())
			->with($opts)
			->through($this->getPipes($opts['definition']))
			->then(function (Builder $builder) {
				$query = $builder->getQuery();

				// Wrap pagination in a closure, so that it's evaluated only if
				// requested
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
			});
	}
}
