<?php
namespace StudioNet\GraphQL\Support\Pipe\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Support\Pipe\Argumentable;

/**
 * OrderByPipe
 *
 * @see Argumentable
 */
class OrderByPipe implements Argumentable {
	/**
	 * handle
	 *
	 * @param  Builder $builder
	 * @param  Closure $next
	 * @param  array $opts
	 * @return void
	 */
	public function handle(Builder $builder, Closure $next, array $opts) {
		if (array_key_exists('order_by', $opts['args'])) {
			foreach ($opts['args']['order_by'] as $token) {
				$order = $token;
				$direction = 'ASC';

				if (preg_match('/^(.*)_(asc|desc)$/i', $token, $matches)) {
					$order = $matches[1];
					$direction = strtoupper($matches[2]);
				}

				$builder->orderBy($order, $direction);
			}
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
		return ['order_by' => ['type' => Type::listOf(Type::string()), 'description' => 'Ordering results']];
	}
}
