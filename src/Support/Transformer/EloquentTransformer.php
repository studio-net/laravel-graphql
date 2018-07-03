<?php
namespace StudioNet\GraphQL\Support\Transformer;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Support\Pipe\Pipeline;

/**
 * EloquentTransformer
 *
 * @see Transformer
 */
abstract class EloquentTransformer extends Transformer {
	/**
	 * {@inheritDoc}
	 *
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|array
	 */
	protected function getResolver(array $opts) {
		return (new Pipeline($this->app))
			->send($opts['source']->newQuery())
			->with($opts)
			->through($this->getPipes($opts['definition']))
			->then(function (Builder $builder) use ($opts) {
				return call_user_func_array([$this, 'then'], [$builder, $opts]);
			});
	}

	/**
	 * This method is called after the pipeline finished
	 *
	 * @param  Builder $builder
	 * @param  array $opts
	 * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|array
	 */
	abstract protected function then(Builder $builder, array $opts);
}
