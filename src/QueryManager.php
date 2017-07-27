<?php
namespace StudioNet\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

class QueryManager {
	/** @var Application $app */
	private $app;

	/**
	 * __construct
	 *
	 * @param  Application $app
	 * @return void
	 */
	public function __construct(Application $app) {
		$this->app = $app;
	}
	/**
	 * fromEntity
	 *
	 * @param  string $model
	 * @return ObjectType
	 */
	public function fromEntity($model) {
		$model = $this->app->make($model);
		$query = [
			'resolve' => $this->getResolver($model)(null, []),
			'args'    => $this->getArguments(),
			'type'    => $this->getType($model)
		];
	}

	/**
	 * Return availabled arguments
	 *
	 * @return array
	 */
	public function getArguments() {
		return [
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}

	/**
	 * Return ObjectType for given model
	 *
	 * @param  Model $model
	 * @return ObjectType
	 */
	public function getType(Model $model) {
		$columns = \Schema::getColumnListing($model->getTable());
		$columns = array_diff($columns, $model->getHidden());
		$fields  = [];

		foreach ($columns as $column) {
			switch ($columns) {
				case 'id' : $type = GraphQLType::nonNull(GraphQL::id()); break;
				default   : $type = GraphQLType::string(); break;
			}

			$resolve = null;
			$field   = [
				'type' => $type
			];

			// Detect relationships
			//if (preg_match($columns))
		}

		return [
			'fields' => [
			
			]
		];
	}

	/**
	 * Return resolver for given model
	 *
	 * @param  Model $model
	 * @return callable
	 */
	public function getResolver(Model $model) {
		return function($root, array $context) use ($model) {
			$primary = $model->getKeyName();
			$builder = $model->newQuery();

			// Retrieve single node
			if (array_key_exists('id', $context)) {
				return $builder->findOrFail($context['id']);
			}

			foreach ($context as $key => $value) {
				switch ($key) {
					case 'after'  : $builder->where($primary, '>', $value) ; break;
					case 'before' : $builder->where($primary, '<', $value) ; break;
					case 'skip'   : $builder->skip($value)                 ; break;
					case 'take'   : $builder->take($value)                 ; break;
				}
			}

			return $builder->get();
		};
	}
}
