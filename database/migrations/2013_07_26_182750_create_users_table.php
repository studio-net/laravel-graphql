<?php
use Illuminate\Database\Migrations\Migration;

/**
 * CreateUsersTable
 *
 * @see Migration
 */
class CreateUsersTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('users', function ($table) {
			$table->increments('id');

			$table->string('name');
			$table->string('email');
			$table->timestamp('last_login')->nullable();
			$table->json('permissions')->nullable();
			$table->boolean('is_admin');
			$table->string('password');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('users');
	}
}
