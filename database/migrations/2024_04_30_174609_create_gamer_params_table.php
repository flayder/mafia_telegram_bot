<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamerParamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gamer_params', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('game_id');  // больше нужно для удобства отбора
            $table->bigInteger('gamer_id'); //уникально для каждого игрока. в разных играх не пересекается
            $table->integer('night');
            $table->string('param_name',100);
            $table->string('param_value');
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
        Schema::dropIfExists('gamer_params');
    }
}
