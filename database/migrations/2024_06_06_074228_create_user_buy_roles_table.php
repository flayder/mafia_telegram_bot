<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBuyRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_buy_roles', function (Blueprint $table) {
            $table->id();
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->integer('role_id');
            $table->bigInteger('game_id')->nullable()->default(null); //в какой игре было активировано. Если null  - то доступно
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
        Schema::dropIfExists('user_buy_roles');
    }
}
