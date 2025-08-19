<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRewardHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reward_history', function (Blueprint $table) {
            $table->id();
            $table->string('group_id',50);
            $table->bigInteger('game_id')->nullable()->default(null);
            $table->string('description',500);
            $table->decimal('buy_sum'); //оригинальная сумма покупки
            $table->decimal('reward_percent');
            $table->decimal('reward_sum');            
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
        Schema::dropIfExists('reward_history');
    }
}
