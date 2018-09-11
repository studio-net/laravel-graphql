<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * CreateLabelsAndLabelablesTables
 *
 * @see Migration
 */
class CreateLabelsAndLabelablesTables extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('labels', function (Blueprint $table) {
			$table->increments('id');
			$table->text('name');
		});
		Schema::create('labelables', function (Blueprint $table) {
			$table->unsignedInteger('label_id');
			$table->unsignedInteger('labelable_id');
			$table->string('labelable_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('labelables');
		Schema::drop('labels');
	}
}
