<?php

namespace StudioNet\GraphQL\Support\Transformer;

use Illuminate\Foundation\Application;
use StudioNet\GraphQL\GraphQL;
use StudioNet\GraphQL\Support\Definition\Definition;
use GraphQL\Type\Definition\ResolveInfo;
use StudioNet\GraphQL\Cache\Cachable;
use StudioNet\GraphQL\Cache\CachePool;

/**
 * Define transformer base class
 *
 * @abstract
 */
abstract class Transformer extends Cachable {
	/** @var Application $app */
	protected $app;

	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @param  CachePool $cache
	 * @return void
	 */
	public function __construct(Application $app, CachePool $cache) {
		parent::__construct($cache);
		$this->app = $app;
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
			'args' => $this->getArguments($definition),
			'resolve' => $this->getResolverCallable($definition),
			'type' => $this->resolveType($definition),
		];
	}

	/**
	 * Resolve type
	 *
	 * @param  Definition $definition
	 * @return \GraphQL\Type\Definition\ObjectType|\GraphQL\Type\Definition\ListOfType
	 */
	abstract public function resolveType(Definition $definition);

	/**
	 * Return resolver callable
	 *
	 * @param  Definition $definition
	 * @return callable
	 */
	public function getResolverCallable(Definition $definition) {
		return function ($root, array $args, $context, ResolveInfo $info) use ($definition) {
			$reflect = new \ReflectionClass($this);

			// check, if Paginable interface was implemented by given Transformer
			// Paginable interface has an extra wrapper for data-fields
			$isPaginable = $this instanceof Paginable;
			$fieldsDepth = $isPaginable ? GraphQL::FIELD_SELECTION_DEPTH + 1 : GraphQL::FIELD_SELECTION_DEPTH;
			$fields = $info->getFieldSelection($fieldsDepth);
			$fieldsForGuessingRelations = $isPaginable ? $fields['items'] : $fields;

			$opts = [
				'root' => $root,
				'args' => array_filter($args),
				'fields' => $fields,
				'context' => $context,
				'info' => $info,
				'transformer' => $this,
				'with' => GraphQL::guessWithRelations($this->app->make($definition->getSource()), $fieldsForGuessingRelations),
				'source' => $this->app->make($definition->getSource()),
				'rules' => $definition->getRules(),
				'filterables' => $definition->getFilterable(),
				'definition' => $definition,
			];

			return call_user_func_array([$this, 'getResolver'], [$opts]);
		};
	}

	/**
	 * Returns resolver
	 *
	 * @param  array $opts
	 * @return mixed
	 */
	abstract protected function getResolver(array $opts);

	/**
	 * Return availabled arguments
	 *
	 * @param  Definition $definition
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getArguments(Definition $definition) {
		$pipes = $this->getPipes($definition);
		$args = [];

		foreach ($pipes as $pipe) {
			$pipe = $this->app->make($pipe);

			if ($pipe instanceof \StudioNet\GraphQL\Support\Pipe\Argumentable) {
				$args = array_merge($args, $pipe->getArguments($definition));
			}
		}

		return $args;
	}

	/**
	 * Returns transformer list
	 *
	 * @return string
	 */
	public function getTransformerName(): string {
		return 'list';
	}

	/**
	 * Returns definition pipes for given transformer name
	 *
	 * @param  Definition $definition
	 * @return array
	 */
	public function getPipes(Definition $definition) {
		$pipes = $definition->getPipes();
		return array_get($pipes, 'all', []) + array_get($pipes, $this->getTransformerName(), []);
	}
}
