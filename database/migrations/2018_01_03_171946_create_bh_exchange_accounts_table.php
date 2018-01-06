<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBhExchangeAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('bh_exchange_accounts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('auth_id')->nullable()->index('auth_id');
			$table->string('exch_name', 255)->nullable()->index('exch_name');
			$table->integer('exch_id')->nullable()->index('exch_id');
			$table->string('auth_key', 255)->nullable();
			$table->string('auth_secret', 255)->nullable();
			$table->string('auth_optional1', 255)->nullable();
			$table->string('auth_nickname', 255)->nullable();
			$table->string('auth_updated', 255)->nullable();
			$table->boolean('auth_active')->nullable();
			$table->boolean('auth_trade')->nullable();
			$table->boolean('exch_trade_enabled')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('bh_exchange_accounts');
	}

}
