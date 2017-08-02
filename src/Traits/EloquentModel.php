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
	/**
	 * Return model relationships
	 *
	 * @return array
	 */
	public function getRelationship() {
		static $relations = [];

		if (!empty($relations)) {
			return $relations;
		}

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

		return $relations;
	}

	/**
	 * Return available columns ; it also append relationships fields : it's
	 * virtual within the database but real in GraphQL schema
	 *
	 * @return array
	 */
	public function getColumns() {
		static $data = [];

		if (!empty($data)) {
			return $data;
		}

		$table      = $this->getTable();
		$connection = $this->getConnection();
		$primary    = $this->getKeyName();
		$columns    = $connection->getSchemaBuilder()->getColumnListing($table);

		// Remove hidden columns : we don't want show or update them. Also
		// append relationships virtual columns
		$related = $this->getRelationship();
		$columns = array_diff($columns, $this->getHidden());
		$columns = array_merge(array_keys($related), $columns);

		foreach (array_unique($columns) as $column) {
			try {
				$type = $connection->getDoctrineColumn($table, $column);
				$type = $type->getType();
			} catch (SchemaException $e) {
				// There's nothing left to do (it's a virtual field or, it
				// also could append with PostgreSQL multiple schemas)
				$data[$column] = null;
				continue;
			}

			// Parse each available database data type and call is related
			// GraphQL type
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

		return $data;
	}
}
