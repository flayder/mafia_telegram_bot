<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();   
            $table->tinyInteger('status')->default(0); //0 - регистрация, 1 - в процессе ,2 - завершена
            $table->string('group_id');
            $table->text('options')->nullable()->default(null);
            $table->tinyInteger('current_night')->default(0);
            $table->string('times_of_day')->default('night');
            $table->boolean('is_team')->default(0);
            $table->timestamp('start_at')->nullable()->default(null);
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
        Schema::dropIfExists('games');
    }
}
