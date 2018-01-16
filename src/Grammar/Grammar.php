<?php
namespace StudioNet\GraphQL\Grammar;

use Illuminate\Database\Eloquent\Builder;

/**
 * Decode filter grammar.
 */
abstract class Grammar {
	const OPERATOR = '/^(\((?<operator>(.*))\))?.*$/';
	const VALUE = '/^(\(.*\))?(\s+)?(?<value>(.*))$/';

	/**
	 * Return SQL operator for given string operator
	 *
	 * @param  string|null $operator
	 * @param  string $value
	 * @return string
	 */
	public function getOperator($operator, $value) {
		switch ($operator) {
			case 'lte': $operator = '<=' ; break;
			case 'lt': $operator = '<'  ; break;
			case 'gt': $operator = '>'  ; break;
			case 'gte': $operator = '>=' ; break;
			default: $operator = (strpos($value, '%') !== false) ? 'like' : '='; break;
		}

		return $operator;
	}

	/**
	 * Return affected builder for given filter
	 *
	 * @param  Builder $builder
	 * @param  array $filter
	 * @return Builder
	 */
	public function getBuilderForFilter(Builder $builder, $filter) {
		foreach ($filter as $key => $value) {
			$builder = $this->getBuilder($builder, $key, $value);
		}

		return $builder;
	}

	/**
	 * Return builder according to filter content.
	 *
	 * @param  Builder $builder
	 * @param  string $key
	 * @param  string|array $value
	 * @param  string $operator
	 *
	 * @return Builder
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	private function getBuilder(Builder $builder, $key, $value, $operator = "AND") {
		if (is_array($value)) {
			$whereFunc = strtolower($operator) === 'or' ? "orWhere": "where";

			$builder->$whereFunc(function ($query) use ($value, $operator, $key) {
				foreach ($value as $command => $v) {
					$command = (strtolower($command) === 'or') ? "OR" : $operator;
					$query = $this->getBuilder($query, $key, $v, $command);
				}
			});
		} else {
			$comparator = $this->getOperator($this->getMatch(self::OPERATOR, $value), $value);
			$value = $this->getMatch(self::VALUE, $value);
			$whereFunc = strtolower($operator) === 'or' ? "orWhereRaw": "whereRaw";

			$builder->$whereFunc("{$this->getKey($key)} {$comparator} ?", [$value]);
		}

		return $builder;
	}

	/**
	 * Return match. Otherwise, return null
	 *
	 * @param  string $matcher
	 * @param  string $data
	 * @return string|null
	 */
	protected function getMatch($matcher, $data) {
		if (preg_match($matcher, $data, $matches)) {
			$key = null;

			switch ($matcher) {
				case self::OPERATOR: $key = 'operator' ; break;
				case self::VALUE: $key = 'value'    ; break;
			}

			// Assert key is defined and exists in matches
			if (!empty($key) and array_key_exists($key, $matches)) {
				return $matches[$key];
			}
		}

		return null;
	}

	/**
	* Return key
	*
	* @param  string $key
	* @return string
	*/
	public function getKey($key) {
		return sprintf('LOWER(%s)', $key);
	}
}
