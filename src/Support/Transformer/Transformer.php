<?php

namespace StudioNet\GraphQL\Support\Transformer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
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
		$app = $this->app;

		return function ($root, array $args, $context, ResolveInfo $info) use ($definition, $app) {
			$reflect = new \ReflectionClass($this);

			// check, if Paginable interface was implemented by given Transformer
			// Paginable interface has an extra wrapper for data-fields
			$isPaginable = $this instanceof Paginable;

			$fieldsDepth = $isPaginable ? 4 : 3; // may be increase depth?
			$fields = $info->getFieldSelection($fieldsDepth);

			$definition->assertAcl(
				str_replace("transformer", "", strtolower($reflect->getShortName())),
				[
					"fields" => $fields,
					"context" => $context,
					"args" => $args,
					'info' => $info,
				]
			);

			$fieldsForGuessingRelations = $isPaginable ? $fields['items'] : $fields;

			$opts = [
				'root' => $root,
				'args' => array_filter($args),
				'fields' => $fields,
				'context' => $context,
				'info' => $info,
				'with' => $this->guessWithRelations($this->app->make($definition->getSource()), $fieldsForGuessingRelations),
				'source' => $app->make($definition->getSource()),
				'rules' => $definition->getRules(),
				'filterables' => $definition->getFilterable(),
				'definition' => $definition,
			];

			return call_user_func_array([$this, 'getResolver'], [$opts]);
		};
	}

	/**
	 * Return relationship based on fields that are queried
	 *
	 * @param  Model $model
	 * @param  array $fields
	 * @param  string $parentRelation
	 *
	 * @return array
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function guessWithRelations(Model $model, array $fields, string $parentRelation = null) {
		$relations = [];
		// Parse each field in order to retrieve relationship elements on root
		// of array (as relationship are based upon multiple resolvers, we just
		// have to handle the root fields here)
		foreach ($fields as $key => $field) {
			if (is_array($field) && method_exists($model, $key)) {
				// verify, that given method returns relation
				$relation = call_user_func([$model, $key]);
				if ($relation instanceof Relation) {
					$relationNameToStore = $parentRelation ? "{$parentRelation}.{$key}" : $key;
					$relations[] = $relationNameToStore;

					// also guess relations for found relation
					$relations = array_merge($relations, $this->guessWithRelations($relation->getModel(), $field, $relationNameToStore));
				}
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
