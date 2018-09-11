<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
	 * @return HasMany
	 */
	public function posts() {
		return $this->hasMany(Post::class);
	}

	/**
	 * Get the phone record associated with the user.
	 *
	 * @return HasOne
	 */
	public function phone() {
		return $this->hasOne(Phone::class);
	}

	public function country() {
		return $this->belongsTo(Country::class);
	}

	public function comments() {
		return $this->morphMany(Comment::class, 'commentable');
	}

	public function labels() {
		return $this->morphToMany(Label::class, 'labelable');
	}
}
