<?php
namespace StudioNet\GraphQL\Support\Model;

use ErrorException;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

trait Relationships {
	/**
	 * Return existing relationships between this model and others
	 *
	 * @return void
	 */
	public function relationships() {
		$model      = new static;
		$relations  = [];
		$reflection = new \ReflectionClass($model);
		$traits     = $reflection->getTraits();
		$exclude    = [__FUNCTION__ => true];

		// Get traits methods and append them to the excluded methods
		foreach ($traits as $trait) {
			foreach ($trait->getMethods() as $method) {
				$exclude[$method->getName()] = true;
			}
		}

		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			// First, we don't want constructor and others related things
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
