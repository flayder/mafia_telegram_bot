<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProhibitKillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prohibit_kills', function (Blueprint $table) {
            $table->id();
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->string('group_id',100)->collation('utf8_general_ci');
            $table->tinyInteger('night_count')->default(0);  //если 0 то полный запрет убивать
            $table->timestamp('expire_time'); //когда истекает срок действия
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
        Schema::dropIfExists('prohibit_kills');
    }
}
