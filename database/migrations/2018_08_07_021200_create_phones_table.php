<?php
use Illuminate\Database\Migrations\Migration;

/**
 * CreatePhonesTable
 *
 * @see Migration
 */
class CreatePhonesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('phones', function ($table) {
			$table->increments('id');

			$table->string('label');
			$table->string('number');
			$table->unsignedInteger('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('phones');
	}
}
