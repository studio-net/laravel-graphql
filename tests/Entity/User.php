<?php
namespace StudioNet\GraphQL\Tests\Entity;

use Illuminate\Database\Eloquent\Model;

/**
 * User
 *
 * @see Model
 */
class User extends Model {
	/** {@inheritDoc} */
	protected $hidden = ['password', 'remember_token'];
}
