<?php
namespace StudioNet\GraphQL\Support\Transformer;

use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Support\Pipe\Pipeline;
use StudioNet\GraphQL\Support\Transformer\Eloquent\StoreTransformer;

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
		$query = $opts['source']->newQuery();

		// this is a small workaround, that should be omitted and reworked with
		// store transformer! add with only, if we don't use store transformer
		if (!($opts['transformer'] instanceof StoreTransformer)) {
			$query->with($opts['with']);
		}

		return (new Pipeline($this->app))
			->send($query)
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
