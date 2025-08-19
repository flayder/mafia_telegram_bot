<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBafsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_bafs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->integer('baf_id');
            $table->integer('amount');  //сколько куплено штук
            $table->boolean('is_activate')->default(1);  //сколько куплено штук
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
        Schema::dropIfExists('user_bafs');
    }
}
