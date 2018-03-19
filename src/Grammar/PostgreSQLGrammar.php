<?php
namespace StudioNet\GraphQL\Grammar;

/**
 * Get operator and binding from string
 */
class PostgreSQLGrammar extends Grammar {
	
	/**
	 * @override
	 */
	public function getKey($key) {
		return $key;
	}

}
