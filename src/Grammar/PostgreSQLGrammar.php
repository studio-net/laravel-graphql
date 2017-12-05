<?php
namespace StudioNet\GraphQL\Grammar;

/**
 * Get operator and binding from string
 */
class PostgreSQLGrammar extends Grammar {
	/**
	 * @override
	 */
	public function getOperator($operator, $value) {
		if ($operator === null and strpos($value, '%') !== false) {
			return 'ilike';
		}

		return parent::getOperator($operator, $value);
	}
	
	/**
	 * @override
	 */
	public function getKey($key) {
		return $key;
	}
}
