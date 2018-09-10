<?php

namespace StudioNet\GraphQL\Tests\GraphQL\Query;

use GraphQL\Type\Definition\Type;
use StudioNet\GraphQL\Support\Definition\Query;

class Unauthorized extends Query {
	/**
	 * {@inheritdoc}
	 */
	protected function authorize(array $args) {
		return false;
	}

	public function getRelatedType() {
		return Type::string();
	}

	public function getResolver() {
		return 'You got this!';
	}
}
