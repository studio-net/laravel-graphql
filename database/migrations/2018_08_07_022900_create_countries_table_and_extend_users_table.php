<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * CreateCountriesTable
 *
 * @see Migration
 */
class CreateCountriesTableAndExtendUsersTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('countries', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name');
		});

		Schema::table('users', function (Blueprint $table) {
			$table->unsignedInteger('country_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('country_id');
		});
		Schema::drop('phones');
	}
}
