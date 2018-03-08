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
		$app = $this->app;

		return function ($root, array $args, $context, ResolveInfo $info) use ($definition, $app) {
			$fields = $info->getFieldSelection(3);
			$reflect = new \ReflectionClass($this);
			$definition->assertAcl(
				str_replace("transformer", "", strtolower($reflect->getShortName())),
				[
					"fields"  => $fields,
					"context" => $context,
				]
			);

			$opts = [
				'root'    => $root,
				'args'    => array_filter($args),
				'fields'  => $fields,
				'context' => $context,
				'info'    => $info,
				'with'    => $this->guessWithRelations($definition, $fields),
				'source'  => $app->make($definition->getSource()),
				'rules'   => $definition->getRules()
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
		$source = $this->app->make($definition->getSource());

		// Parse each field in order to retrieve relationship elements on root
		// of array (as relationship are based upon multiple resolvers, we just
		// have to handle the root fields here)
		foreach ($fields as $key => $field) {
			// TODO Improve this checker
			if (is_array($field) and method_exists($source, $key)) {
				array_push($relations, $key);
			}
		}

		return $relations;
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
