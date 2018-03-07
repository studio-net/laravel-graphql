<?php
use Illuminate\Database\Migrations\Migration;

/**
 * CreateTagsTable
 *
 * @see Migration
 */
class CreateTagsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('tags', function ($table) {
			$table->increments('id');

			$table->string('name');

			$table->timestamps();
		});

		Schema::create('tag_post', function ($table) {
			$table->integer('post_id')->unsigned();
			$table->integer('tag_id')->unsigned();

			$table->foreign('post_id')->references('id')->on('posts');
			$table->foreign('tag_id')->references('id')->on('tags');

			$table->primary(['post_id', 'tag_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('tags');
	}
}
