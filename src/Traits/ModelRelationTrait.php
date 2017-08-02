<?php
namespace StudioNet\GraphQL\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass, ReflectionMethod, ErrorException;

/**
 * Implements method `getRelations`
 */
trait ModelRelationTrait {
	/**
	 * Return model relationships
	 *
	 * @param  Model $model
	 * @return array
	 */
	public function getRelations(Model $model) {
		$relations  = [];
		$reflection = new ReflectionClass($model);
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

		return $relations;
	}
}
