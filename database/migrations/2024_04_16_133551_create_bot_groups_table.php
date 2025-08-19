<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_groups', function (Blueprint $table) {
            $table->string('id',50)->primary();
            $table->string('title');
            $table->string('group_type');
            $table->string('group_link',1000)->nullable()->default(null);
            $table->string('who_add',50)->nullable()->default(null);
         //   $table->text('options')->nullable()->default(null);
            $table->integer('tarif_id')->default(1);
            $table->timestamp('tarif_expired')->default(null);
            $table->decimal('reward')->default(3);
            $table->decimal('balance')->default(0); //награда, доступная к выводу
            $table->decimal('total_reward')->default(0); //награда за всё время
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
        Schema::dropIfExists('bot_groups');
    }
}
