<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;
use StudioNet\GraphQL\Traits\EloquentModel;

/**
 * User
 *
 * @see Model
 */
class User extends Model {
	use EloquentModel;

	/** @var array $hidden */
	protected $hidden = ['password'];

	/**
	 * Return related posts
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function posts() {
		return $this->hasMany(Post::class);
	}
}
