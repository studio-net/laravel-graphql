<?php
namespace StudioNet\GraphQL\Traits;

use GraphQL\Type\Definition\Type as GraphQLType;
use Doctrine\DBAL\Schema\SchemaException;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass, ReflectionMethod, ErrorException;

/**
 * Implements method `getRelations` and `getColumns`
 */
trait EloquentModel {
	/** @var array $columns */
	private $columns = null;

	/** @var array $relationships */
	private $relationships = null;

	/**
	 * Return model relationships
	 *
	 * @return array
	 */
	public function getRelationship() {
		if (is_null($this->relationships)) {
			$relations  = [];
			$reflection = new ReflectionClass($this);
			$traits     = $reflection->getTraits();
			$exclude    = [];

			// Get traits methods and append them to the excluded methods
			foreach ($traits as $trait) {
				foreach ($trait->getMethods() as $method) {
					$exclude[$method->getName()] = true;
				}
			}

			foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				if ($method->class !== get_class($this)) {
					continue;
				}

				// We don't want method with parameters (relationship doesn't
				// have parameter)
				if (!empty($method->getParameters())) {
					continue;
				}

				// We don't want parsing this current method
				if (array_key_exists($method->getName(), $exclude)) {
					continue;
				}

				try {
					$return = $method->invoke($this);

					// Get only method that returned Relation instance
					if ($return instanceof Relation) {
						$name   = $method->getName();
						$type   = with(new ReflectionClass($return))->getShortName();
						$model  = with(new ReflectionClass($return->getRelated()));

						// Assert that relationship field handle this trait :
						// otherwise, we cannot check columns and relationships
						if (!array_key_exists(__TRAIT__, $model->getTraits())) {
							continue;
						}

						$relations[$name] = [
							'field' => $method->getName(),
							'type'  => $type,
							'model' => $model->getName()
						];
					}
				} catch (ErrorException $e) {}
			}

			$this->relationships = $relations;
		}

		return $this->relationships;
	}

	/**
	 * Return available columns ; it also append relationships fields : it's
	 * virtual within the database but real in GraphQL schema
	 *
	 * @return array
	 */
	public function getColumns() {
		if (is_null($this->columns)) {
			$data       = [];
			$table      = $this->getTable();
			$connection = $this->getConnection();
			$primary    = $this->getKeyName();
			$columns    = $connection->getSchemaBuilder()->getColumnListing($table);

			// Remove hidden columns : we don't want show or update them. Also
			// append relationships virtual columns
			$related = $this->getRelationship();
			$columns = array_diff($columns, $this->getHidden());
			$columns = array_merge(array_keys($related), $columns);
			$casts   = $this->getGenericCasts();

			foreach (array_unique($columns) as $column) {
				if (array_key_exists($column, $casts)) {
					$type = $casts[$column];
				} else {
					try {
						$type = $connection->getDoctrineColumn($table, $column);
						$type = $type->getType()->getName();
					} catch (SchemaException $e) {
						// There's nothing left to do (it's a virtual field or,
						// it also could append with PostgreSQL multiple
						// schemas)
						$data[$column] = null;
						continue;
					}
				}

				// Parse each available database data type and call is related
				// GraphQL type
				switch ($type) {
				case 'real'       :
				case 'int'        :
				case 'integer'    : $type = GraphQLType::int()            ; break;
				case 'double'     :
				case 'float'      : $type = GraphQLType::float()          ; break;
				case 'date'       :
				case 'datetime'   : $type = \GraphQL::scalar('timestamp') ; break;
				case 'boolean'    : $type = GraphQLType::boolean()        ; break;
				case 'object'     :
				case 'array'      :
				case 'collection' : $type = \GraphQL::scalar('array')     ; break;
				default           : $type = GraphQLType::string()         ; break;
				}

				// Assert primary key is an id
				if ($column === $primary) {
					$type = GraphQLType::id();
				}

				$data[$column] = $type;
			}

			$this->columns = $data;
		}

		return $this->columns;
	}

	/**
	 * Return generic cast : use date and casts parameters in one single array
	 * statement
	 *
	 * @return array
	 */
	public function getGenericCasts() {
		$dates = array_flip($this->getDates());
		$dates = array_map(function() { return 'datetime'; }, $dates);
		$casts = $this->getCasts();

		return array_merge($dates, $casts);
	}
}
