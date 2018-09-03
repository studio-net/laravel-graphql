<?php

namespace StudioNet\GraphQL\Tests\GraphQL\Query;

use StudioNet\GraphQL\Definition\Type;
use StudioNet\GraphQL\Support\Definition\Query;

class SimpleString extends Query {
	public function getRelatedType() {
		return Type::string();
	}

	public function getResolver() {
		return 'You got this!';
	}
}
