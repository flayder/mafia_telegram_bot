<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupTarifsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_tarifs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('max_gamer_count');
            $table->integer('price');  //в виндкоинах
            $table->decimal('reward')->default(0);
            $table->text('role_ids')->nullable()->default(null);  //если null  - то доступность определить по max_gamer_count
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
        Schema::dropIfExists('group_tarifs');
    }
}
