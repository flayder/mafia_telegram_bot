<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableGameRoleIsvew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_roles', function (Blueprint $table) {
			  $table->tinyInteger('view_role_type_id')->nullable()->default(null);
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_roles', function (Blueprint $table) {
			$table->dropColumn('view_role_type_id');
		});
    }
}
