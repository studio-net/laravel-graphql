<?php
namespace StudioNet\GraphQL\Grammar;

use Illuminate\Database\Eloquent\Builder;
use StudioNet\GraphQL\Filter\FilterInterface;
use StudioNet\GraphQL\Exception\FilterException;

/**
 * Decode filter grammar.
 */
abstract class Grammar {
	const OPERATOR = '/^(\((?<operator>(.*))\))?.*$/';
	const VALUE = '/^(\(.*\))?(\s+)?(?<value>(.*))$/';

	/**
	 * Return affected builder for given filter
	 *
	 * @param  Builder $builder
	 * @param  array $filter
	 * @param  array $filterables
	 * @return Builder
	 */
	public function getBuilderForFilter(Builder $builder, $filter, $filterables) {
		foreach ($filter as $key => $value) {
			$builder = $this->getBuilder($builder, [
				'key' => $key,
				'value' => $value,
				'filter' => $filterables[$key] ?? true,
			]);
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
	private function getBuilder(Builder $builder, $filter, $operator = "AND") {
		$whereFunc = strtolower($operator) === 'or' ? "orWhere": "where";

		$builder->$whereFunc(function ($b) use ($filter) {
			$resolver = $filter['filter'];
			// check, if we got an array, and try fetch resolver
			if (is_array($filter['filter'])) {
				if (key_exists('resolver', $filter['filter'])) {
					$resolver = $filter['filter']['resolver'];
				} else {
					throw new FilterException("Invalid filter for $filter[key]");
				}
			}

			// if we got simple closure, call it
			if (is_callable($resolver)) {
				$resolver($b, $filter['value'], $filter['key']);
				return;
			}

			// if we got instance of FilterInterface, execute it
			if ($resolver instanceof FilterInterface) {
				$resolver->updateBuilder(
					$b,
					$filter['value'],
					$filter['key']
				);
				return;
			}

			throw new FilterException("Invalid filter for $filter[key]");
		});

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
