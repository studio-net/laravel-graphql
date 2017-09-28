<?php
namespace StudioNet\GraphQL\Support\Eloquent;

use Doctrine\DBAL\Schema\SchemaException;
use GraphQL\Type\Definition\Type as GraphQLType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass, ReflectionMethod, ErrorException;
use StudioNet\GraphQL\Cache\Cachable;

/**
 * Get model attributes like columns, relationships, etc.
 */
class ModelAttributes extends Cachable {
	/**
	 * {@inheritDoc}
	 */
	public function getCacheNamespace() {
		return 'model.attributes';
	}

	/**
	 * Return cache key
	 *
	 * @param  string $kind
	 * @param  Model  $model
	 * @return string
	 */
	public function getKey($kind, Model $model) {
		return sprintf('%s.%s', $kind, $model->getTable());
	}

	/**
	 * Return model relations
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function getRelations(Model $model) {
		$key = $this->getKey('relation', $model);

		if (!$this->has($key)) {
			$relations  = [];
			$reflection = new ReflectionClass($model);

			// Parse each public methods (a relationship must be defined as
			// public) in order to reduce list of possibilites
			foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				// Assert method come from our model and not trait or extended
				if ($method->class !== get_class($model)) {
					continue;
				}

				// We don't want method with parameters (relationship doesn't
				// have parameter)
				if (!empty($method->getParameters())) {
					continue;
				}

				// Relationships are named a lower method names
				if (!preg_match("/^[a-z]+$/", $method->getName())) {
					continue;
				}

				try {
					$return = $method->invoke($model);

					// Get only method that returned Relation instance
					if ($return instanceof Relation) {
						$name    = $method->getName();
						$type    = with(new ReflectionClass($return))->getShortName();
						$related = with(new ReflectionClass($return->getRelated()));

						$relations[$name] = [
							'field' => $method->getName(),
							'type'  => $type,
							'model' => $related->getName()
						];
					}
				} catch (\Exception $e) {}
			}

			$this->save($key, $relations);
		}

		return $this->get($key);
	}

	/**
	 * Return available columns ; it also append relationships fields : it's
	 * virtual within the database but real in GraphQL schema
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function getColumns(Model $model) {
		$key = $this->getKey('columns', $model);

		if (!$this->has($key)) {
			$data       = [];
			$table      = $model->getTable();
			$connection = $model->getConnection();
			$primary    = $model->getKeyName();
			$columns    = $connection->getSchemaBuilder()->getColumnListing($table);

			// Remove hidden columns : we don't want show or update them. Also
			// append relationships virtual columns
			$related = $this->getRelations($model);
			$columns = array_diff($columns, $model->getHidden());
			$columns = array_merge(array_keys($related), $columns);
			$casts   = $this->getGenericCasts($model);

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

			$this->save($key, $data);
		}

		return $this->get($key);
	}

	/**
	 * Return generic cast : use date and casts parameters in one single array
	 * statement
	 *
	 * @param  Model $model
	 * @return array
	 */
	private function getGenericCasts(Model $model) {
		$dates = array_flip($model->getDates());
		$dates = array_map(function() { return 'datetime'; }, $dates);
		$casts = $model->getCasts();

		return array_merge($dates, $casts);
	}
}
