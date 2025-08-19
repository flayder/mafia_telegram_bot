<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('set_key',100);
            $table->string('title',100);
            $table->string('description',1000)->nullable()->default(null);
            $table->string('set_value');
            $table->string('variants',1000)->nullable()->default(null);
            $table->integer('tarif_id')->default(0);
            $table->string('group_id',100)->collation('utf8_general_ci')->nullable()->default(null);
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
        Schema::dropIfExists('settings');
    }
}
