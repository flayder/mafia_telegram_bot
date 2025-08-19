<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveBafsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_bafs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('game_id');
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->integer('baf_id');
            $table->boolean('need_decrement')->default(0); //нужно ли списывать каждый раз при использовании
            $table->boolean('is_active')->default(1);  //некоторые можно деактивировать после использования. получаются одноразовые
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
        Schema::dropIfExists('active_bafs');
    }
}
