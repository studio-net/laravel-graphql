<?php
namespace StudioNet\GraphQL\Eloquent;

use ErrorException;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Doctrine\DBAL\Schema\SchemaException;
use ReflectionClass;
use ReflectionMethod;

abstract class Manager {
	/** @var Application $app */
	protected $app;

	/** @var array $cache */
	protected $cache = [];

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
	 * Return columns name for given model
	 *
	 * @param  Model $model
	 * @param  array $include
	 * @return array
	 */
	protected function getColumns(Model $model, array $include = []) {
		$key = 'columns:' . get_class($model);

		if (empty($this->cache[$key])) {
			$table    = $model->getTable();
			$primary  = $model->getKeyName();
			$doctrine = \DB::connection();
			$columns  = \Schema::getColumnListing($model->getTable());
			$columns  = array_diff($columns, $model->getHidden());
			$columns  = array_merge($columns, array_keys($include));
			$data     = [];

			foreach (array_unique($columns) as $column) {
				try {
					$type = $doctrine->getDoctrineColumn($table, $column)->getType();
				} catch (SchemaException $e) {
					$data[$column] = null;
					continue;
				}

				switch ($type->getName()) {
					case 'smallint'     :
					case 'bigint'       :
					case 'integer'      : $type = GraphQLType::int()                         ; break;
					case 'decimal'      :
					case 'float'        : $type = GraphQLType::float()                       ; break;
					case 'date'         :
					case 'datetimetz'   :
					case 'time'         :
					case 'datetime'     : $type = \GraphQL::scalar('timestamp')              ; break;
					case 'array'        :
					case 'simple_array' : $type = GraphQLType::listOf(GraphQLType::string()) ; break;
					default             : $type = GraphQLType::string()                      ; break;
				}

				// Assert primary key is an id
				if ($column === $primary) {
					$type = GraphQLType::id();
				}

				$data[$column] = $type;
			}

			$this->cache[$key] = $data;
		}
		
		return $this->cache[$key];
	}

	/**
	 * Return availabled arguments
	 *
	 * @param  bool $plural
	 * @return array
	 */
	protected function getArguments($plural = false) {
		if ($plural === false) {
			return [
				'id' => ['type' => GraphQLType::nonNull(GraphQLType::id()), 'description' => 'Primary key lookup']
			];
		}

		return [
			'after'  => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'before' => ['type' => GraphQLType::id()  , 'description' => 'Based-cursor navigation' ] ,
			'skip'   => ['type' => GraphQLType::int() , 'description' => 'Offset-based navigation' ] ,
			'take'   => ['type' => GraphQLType::int() , 'description' => 'Limit-based navigation'  ] ,
		];
	}


	/**
	 * Return relationships
	 *
	 * @param  Model $model
	 * @return array
	 */
	protected function getRelations(Model $model) {
		$key = 'relation:' . get_class($model);

		if (empty($this->cache[$key])) {
			$relations  = [];
			$reflection = new \ReflectionClass($model);
			$traits     = $reflection->getTraits();
			$exclude    = [];

			// Get traits methods and append them to the excluded methods
			foreach ($traits as $trait) {
				foreach ($trait->getMethods() as $method) {
					$exclude[$method->getName()] = true;
				}
			}

			foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				if ($method->class !== get_class($model)) {
					continue;
				}

				// We don't want method with parameters (relationship doesn't have
				// parameter)
				if (!empty($method->getParameters())) {
					continue;
				}

				// We don't want parsing this current method
				if (array_key_exists($method->getName(), $exclude)) {
					continue;
				}

				try {
					$return = $method->invoke($model);

					// Get only method that returned Relation instance
					if ($return instanceof Relation) {
						$name = $method->getName();

						$relations[$name] = [
							'field' => $method->getName(),
							'type'  => (new ReflectionClass($return))->getShortName(),
							'model' => (new ReflectionClass($return->getRelated()))->getName()
						];
					}
				} catch (ErrorException $e) {}
			}

			$this->cache[$key] = $relations;
		}

		return $this->cache[$key];
	}
}
