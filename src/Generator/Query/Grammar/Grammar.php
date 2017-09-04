<?php
namespace StudioNet\GraphQL\Generator\Query\Grammar;

/**
 * Decode filter grammar.
 */
abstract class Grammar {

	const OPERATOR = '/^(\((?<operator>(.*))\))?.*$/';
	const VALUE  = '/^(\(.*\))?(\s+)?(?<value>(.*))$/';

	/**
	 * Return SQL operator for given string operator
	 *
	 * @param  string $operator
	 * @param  string $value
	 * @return string
	 */
	public function getOperator($operator, $value) {
		switch ($operator) {
			case 'lte' : $operator = '<=' ; break;
			case 'lt'  : $operator = '<'  ; break;
			case 'gt'  : $operator = '>'  ; break;
			case 'gte' : $operator = '>=' ; break;
			default    : $operator = (strpos($value, '%') !== false) ? 'like' : '='; break;
		}

		return $operator;
	}

	/**
	 *
	 * @return [type] [description]
	 */
	public function getBuilderForFilter($builder, $filter) {
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
	 * @return string
	 */
	private function getBuilder($builder, $key, $value, $operator = "AND") {
		$expressions = [];

		if (is_array($value)) {

			$whereFunc = strtolower($operator) === 'or' ? "orWhere": "where";
			$builder->$whereFunc(function($query) use ($value, $operator, $key) {
				foreach ($value as $command => $v) {
					$command = (strtolower($command) === 'or') ? "OR" : $operator;
					$query = $this->getBuilder($query, $key, $v, $command);
				}
			});

		} else {
			$comparator = $this->getOperator($this->getMatch(self::OPERATOR, $value), $value);
			$value = $this->getMatch(self::VALUE, $value);
			$whereFunc = strtolower($operator) === 'or' ? "orWhere": "where";
			$builder->$whereFunc($this->getKey($key), $comparator, $value);
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
			switch ($matcher) {
				case self::OPERATOR : $key = 'operator'; break;
				case self::VALUE  : $key = 'value' ; break;
			}
			// Assert key is defined and exists in matches
			if (isset($key) and array_key_exists($key, $matches)) {
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
