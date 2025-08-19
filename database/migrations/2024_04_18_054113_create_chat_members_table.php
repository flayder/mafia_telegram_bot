<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_members', function (Blueprint $table) {
            $table->id();
            $table->string('member_id',50)->collation('utf8_general_ci');
            $table->string('group_id',50)->collation('utf8_general_ci');
            $table->string('username')->nullable()->default(null);
            $table->string('first_name')->nullable()->default(null);
            $table->string('last_name')->nullable()->default(null);
            $table->string('role',50)->collation('utf8_general_ci')->default('user');
            $table->boolean('is_bot')->default(0);
            $table->boolean('is_premium')->default(0);            
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
        Schema::dropIfExists('chat_members');
    }
}
