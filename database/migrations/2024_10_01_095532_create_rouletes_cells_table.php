<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouletesCellsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roulettes_cells', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('roulette_id');
            $table->tinyInteger('cell_number'); // от 0 до 29, всего 30            
            $table->integer('prize_id'); //если 0, то нет приза
            $table->boolean('is_open')->default(0);
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
        Schema::dropIfExists('roulettes_cells');
    }
}
