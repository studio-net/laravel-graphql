<?php
namespace StudioNet\GraphQL\Support\Pipe\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Support\Pipe\Argumentable;

/**
 * TakePipe
 *
 * @see Argumentable
 */
class TakePipe implements Argumentable {
	/**
	 * handle
	 *
	 * @param  Builder $builder
	 * @param  Closure $next
	 * @param  array $opts
	 * @return void
	 */
	public function handle(Builder $builder, Closure $next, array $opts) {
		if (array_key_exists('take', $opts['args'])) {
			$builder->take($opts['args']['take']);
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
		return ['take' => ['type' => Type::int(), 'description' => 'Limit-based navigation']];
	}
}
