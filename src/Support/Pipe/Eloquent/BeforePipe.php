<?php
namespace StudioNet\GraphQL\Support\Pipe\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\Definition;
use StudioNet\GraphQL\Support\Pipe\Argumentable;

/**
 * BeforePipe
 *
 * @see Argumentable
 */
class BeforePipe implements Argumentable {
	/**
	 * handle
	 *
	 * @param  Builder $builder
	 * @param  Closure $next
	 * @param  array $opts
	 * @return void
	 */
	public function handle(Builder $builder, Closure $next, array $opts) {
		if (array_key_exists('before', $opts['args'])) {
			$builder->where($opts['source']->getKeyName(), '<', $opts['args']['before']);
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
		return ['before' => ['type' => Type::id(), 'description' => 'Cursor-based navigation']];
	}
}
