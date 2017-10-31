<?php
namespace StudioNet\GraphQL\Support\Transformer;

use Illuminate\Foundation\Application;
use StudioNet\GraphQL\Support\Definition\Definition;
use GraphQL\Type\Definition\ResolveInfo;
use StudioNet\GraphQL\Cache\Cachable;
use StudioNet\GraphQL\Cache\CachePool;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Define transformer base class
 *
 * @abstract
 */
abstract class Transformer extends Cachable {
	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @return void
	 */
	public function __construct(Application $app, CachePool $cache) {
		$this->cache = $cache;
		$this->app   = $app;
	}

	/**
	 * {@inheritDoc}
	 * Use inherit cache for all transformers in order to fetch existing
	 * InputObjectType and other things
	 *
	 * @return string
	 */
	public function getCacheNamespace() {
		return 'graphql.transformer';
	}

	/**
	 * Transform given definition
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function transform(Definition $definition) {
		return [
			'args'    => $this->getArguments($definition),
			'resolve' => $this->getResolverCallable($definition),
			'type'    => $this->resolveType($definition),
		];
	}

	/**
	 * Return resolver callable
	 *
	 * @param  Definition $definition
	 * @return callable
	 */
	public function getResolverCallable(Definition $definition) {
		$app = $this->app;

		return function($root, array $args, $context, ResolveInfo $info) use ($definition, $app) {
			$fields = $info->getFieldSelection(3);
			$opts   = [
				'root'    => $root,
				'args'    => $args,
				'fields'  => $fields,
				'context' => $context,
				'info'    => $info,
				'with'    => $this->guessWithRelations($definition, $fields),
				'source'  => $app->make($definition->getSource())
			];

			return call_user_func_array([$this, 'getResolver'], [$opts]);
		};
	}

	/**
	 * Return relationship based on entity definition construction
	 *
	 * @param  Definition $definition
	 * @param  array $fields
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function guessWithRelations(Definition $definition, array $fields) {
		$relations = [];

		// Parse each field in order to retrieve relationship elements on root
		// of array (as relationship are based upon multiple resolvers, we just
		// have to handle the root fields here)
		foreach ($fields as $key => $field) {
			if (is_array($field)) {
				array_push($relations, $key);
			}
		}

		return $relations;
	}

	/**
	 * Return GraphQL\Type\Definition\InputObjectType for given definition
	 *
	 * @param  Definition $definition
	 * @return InputObjectType
	 */
	protected function getInputType(Definition $definition) {
		$key = sprintf('%sArguments', ucfirst($definition->getName()));

		if (!$this->has($key)) {
			$input = new InputObjectType([
				'name'   => sprintf('%sArguments', ucfirst($definition->getName())),
				'fields' => [$definition, 'getMutable']
			]);

			$this->save($key, $input);
		}

		return $this->get($key);
	}

	/**
	 * Return availabled arguments
	 *
	 * @param  Definition $definition
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getArguments(Definition $definition) {
		return [];
	}
}
