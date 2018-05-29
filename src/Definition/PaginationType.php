<?php
namespace StudioNet\GraphQL\Definition;

use GraphQL\Type\Definition\ObjectType;

/**
 * Represent pagination informations
 */
class PaginationType extends ObjectType {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct([
			'name' => 'Pagination',
			'description' => "Pagination Informations",
			'fields' => [
				'totalCount' => [
					"description" => "Total number of items",
					"type" => Type::int(),
				],
				'page' => [
					"description" => "Current page number (zero indexed)",
					"type" => Type::int(),
				],
				'numPages' => [
					"description" => "Total number of pages",
					"type" => Type::int(),
				],
				'hasNextPage' => [
					"description" => "Is there a next page",
					"type" => Type::bool(),
				],
				'hasPreviousPage' => [
					"description" => "Is there a previous page",
					"type" => Type::bool(),
				],
			]
		]);
	}
}
