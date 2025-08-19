<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnionParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('union_participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('union_id');
            $table->bigInteger('gamer_id');
            $table->bigInteger('game_id');   //игра тоже нужна для фильтра
            $table->integer('pos_in_union')->default(0);
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
        Schema::dropIfExists('union_participants');
    }
}
