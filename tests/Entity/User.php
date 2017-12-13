<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;

/**
 * User
 *
 * @see Model
 */
class User extends Model {
	/** @var array $hidden */
	protected $hidden = ['password'];

	/** @var array $guarded */
	protected $guarded = [];

	/** @var array $dates */
	protected $dates = [
		'last_login'
	];

	/** @var array $casts */
	protected $casts = [
		'is_admin' => 'boolean',
		'permissions' => 'array'
	];

	/**
	 * Return related posts
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function posts() {
		return $this->hasMany(Post::class);
	}
}
