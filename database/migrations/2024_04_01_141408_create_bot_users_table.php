<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_users', function (Blueprint $table) {
            $table->string('id',50)->collation('utf8_general_ci')->primary();
            $table->string('nick_name')->nullable()->default(null);
            $table->string('first_name')->nullable()->default(null);
            $table->string('last_name')->nullable()->default(null);
            $table->text('balances')->nullable()->default(null); //балансы всех валют
            $table->string('referal_link_id',1000)->default(null);
            $table->string('referer_id',50)->collation('utf8_general_ci')->nullable()->default(null);
            $table->text('options')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bot_users');
    }
}
