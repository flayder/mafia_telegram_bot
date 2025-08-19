<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSleepKillRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sleep_kill_roles', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->integer('role_id');
            $table->string('need_commands',2000);  //через запятую
            $table->tinyInteger('test_nights_count');  //сколько ночей просматривать
            $table->boolean('is_one')->default(0);  //один раз за игру. test_nights  игнорируем если is_one
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
        Schema::dropIfExists('sleep_kill_roles');
    }
}
