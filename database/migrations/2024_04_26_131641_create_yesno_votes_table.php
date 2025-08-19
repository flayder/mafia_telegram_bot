<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYesnoVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('yesno_votes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('voiting_id'); 
            $table->bigInteger('gamer_id');
            $table->string('vote_user_id',50); //голосующий
            $table->integer('vote_role_id'); //роль голосовавшего
            $table->string('answer',10);
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
        Schema::dropIfExists('yesno_votes');
    }
}
