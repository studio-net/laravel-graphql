<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;

/**
 * Post
 *
 * @see Model
 */
class Post extends Model {
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

	/**
	 * Return related tags
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function tags() {
		return $this->belongsToMany(
			Tag::class, 
			'tag_post',
			'post_id',
			'tag_id'
		);	
	}

}
