<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGameRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_game_roles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('game_id');
            $table->string('user_id')->collation('utf8_general_ci');
            $table->integer('role_id');
            $table->integer('first_role_id')->default(0);
            $table->boolean('is_active')->default(1);
            $table->integer('kill_night_number')->default(0); //в какую ночь убит. Если 0 - значит живой
            $table->bigInteger('killer_id')->default(0);
            $table->string('killers',2000)->collation('utf8_general_ci')->nullable()->default(null);
            $table->tinyInteger('team')->default(0);
            $table->string('message_id')->nullable()->default(null);
            $table->integer('sort_id')->default(0);
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
        Schema::dropIfExists('user_game_roles');
    }
}
