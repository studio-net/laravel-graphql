<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;

/**
 * Post
 *
 * @see Model
 */
class Tag extends Model {
	/** @var array $fillable */
	protected $fillable = ['name'];

	/**
	 * Return related posts
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function posts() {
		return $this->belongsToMany(
			Post::class, 
			'tag_post',
			'tag_id',
			'post_id'
		);
	}
}
