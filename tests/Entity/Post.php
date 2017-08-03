<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;
use StudioNet\GraphQL\Traits\EloquentModel;

/**
 * Post
 *
 * @see Model
 */
class Post extends Model {
	use EloquentModel;

	/** @var array $fillable */
	protected $fillable = ['title', 'content'];

	/**
	 * Return related posts
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function author() {
		return $this->belongsTo(User::class);
	}
}
