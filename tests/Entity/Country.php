<?php

namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Country extends Model
{
	/**
	 * Get all of the posts for the country.
	 *
	 * @return HasManyThrough
	 */
	public function posts()
	{
		return $this->hasManyThrough(Post::class, User::class);
	}
}