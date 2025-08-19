<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoulettesPrizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roulettes_prizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->tinyInteger('percent');
            $table->tinyInteger('season')->default(0);  //если не 0, тогда доступно только по сезону
            $table->tinyInteger('prize_type')->default(0);
            $table->string('add_function'); //функция, при выбивании клетки
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
        Schema::dropIfExists('roulettes_prizes');
    }
}
