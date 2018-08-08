<?php

namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phone extends Model
{
	public $timestamps = false;

	/**
	 * Get the user that owns the phone.
	 *
	 * @return BelongsTo
	 */
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}