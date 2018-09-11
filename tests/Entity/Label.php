<?php

namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Label extends Model {
	public $timestamps = false;

	public function posts() {
		return $this->morphedByMany(Post::class, 'labelable');
	}

	public function users() {
		return $this->morphedByMany(User::class, 'labelable');
	}
}
