<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameRolesOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_roles_order', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id');
            $table->integer('position');
            $table->integer('gamers_min')->default(0);
            $table->integer('gamers_max')->default(5000); //заоблачное число. столько игроков точно не будет
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
        Schema::dropIfExists('game_roles_order');
    }
}
