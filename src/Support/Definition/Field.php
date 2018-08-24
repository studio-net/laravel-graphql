<?php
namespace StudioNet\GraphQL\Support\Definition;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use StudioNet\GraphQL\GraphQL;

/**
 * Represent a field
 *
 * @see FieldInterface
 * @abstract
 */
abstract class Field implements FieldInterface {
	/** @var Application $app */
	protected $app;

	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @param  CachePool $cache
	 * @return void
	 */
	public function __construct(Application $app) {
		$this->app = $app;
	}

	/**
	 * Return field name
	 *
	 * @return string
	 */
	public function getName() {
		return array_last(explode('\\', strtolower(get_called_class())));
	}

	/**
	 * Return field description
	 *
	 * @return string
	 */
	public function getDescription() {
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAttributes() {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getArguments() {
		return [];
	}

	/**
	 * An optional definition of source model for root of the query/mutation.
	 * Used to resolve eager loading in custom queries.
	 *
	 * @return Model|null
	 */
	public function getSource() {
		return;
	}

	/**
	 * Resolve as array
	 *
	 * @return array
	 */
	public function resolveType() {
		$attributes = $this->getAttributes() + [
			'type' => $this->getRelatedType(),
			'args' => $this->getArguments(),
			'description' => $this->getDescription()
		];

		// Append resolver if exists
		if (method_exists($this, 'getResolver')) {
			$attributes['resolve'] = function ($root, array $args, $context, ResolveInfo $info) {
				$fields = $info->getFieldSelection(GraphQL::FIELD_SELECTION_DEPTH);

				$opts = [
					'root'    => $root,
					'args'    => $args,
					'context' => $context,
					'info'    => $info,
					'fields'  => $fields,
					'with'    => []
				];

				// if getSource() returns some model, then guess relation for eager loading
				if (method_exists($this, 'getSource') && is_string($this->getSource())) {
					$source = $this->app->make($this->getSource());
					if ($source instanceof Model) {
						$opts['with'] = GraphQL::guessWithRelations($source, $fields);
					}
				}

				return call_user_func_array([$this, 'getResolver'], [$opts]);
			};
		}

		return $attributes;
	}
}
