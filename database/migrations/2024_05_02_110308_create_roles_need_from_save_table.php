<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesNeedFromSaveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles_need_from_save', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id');  //роль, от которой спасают
            $table->integer('saved_role_id'); //роль, которая спасает            
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
        Schema::dropIfExists('roles_need_from_save');
    }
}
