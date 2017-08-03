<?php
use Illuminate\Database\Migrations\Migration;

/**
 * CreatePostsTable
 *
 * @see Migration
 */
class CreatePostsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('posts', function ($table) {
			$table->increments('id');
			$table->string('title');
			$table->text('content');
			$table->integer('user_id');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('posts');
	}
}
