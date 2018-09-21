<?php

namespace StudioNet\GraphQL\Support\Transformer\Eloquent\Relation;

use Illuminate\Database\Eloquent\Model;

class RelationTransformerFactory {
	public static function getTransformer(Model $model, string $column, array $values) {
		$relation = $model->{$column}();
		$classesToTest = [];

		// Try if transformer exists like {RelationName}RelationTransformee
		$classesToTest[] = (new \ReflectionClass($relation))->getShortName();

		$eloquentNs = 'Illuminate\Database\Eloquent\Relations';
		// If Relation is an override of an Eloquent relation type, try to get
		// ancestor to find "Generic" relation.
		if (strpos(get_class($relation), $eloquentNs) === false) {
			$parents = class_parents($relation);
			foreach ($parents as $parent) {
				if (strpos($parent, $eloquentNs) === false) {
					continue;
				}
				$classesToTest[] = (new \ReflectionClass($parent))->getShortName();
				break;
			}
		}

		foreach ($classesToTest as $classToTest) {
			$directClass = str_replace("Abstract", $classToTest, AbstractRelationTransformer::class);
			if (class_exists($directClass)) {
				return new $directClass($model, $column, $values);
			}
		}

		// Fallback
		return new DefaultRelationTransformer($model, $column, $values);
	}
}
