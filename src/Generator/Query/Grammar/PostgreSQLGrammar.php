<?php
namespace StudioNet\GraphQL\Generator\Query\Grammar;

/**
 * Get operator and binding from string
 */
class PostgreSQLGrammar extends Grammar {
	/**
	 * @override
	 */
	public function getOperator($operator, $value) {
		if (is_null($operator) and strpos($value, '%') !== false) {
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
