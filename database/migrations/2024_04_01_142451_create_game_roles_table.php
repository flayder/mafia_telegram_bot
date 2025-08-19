<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_roles', function (Blueprint $table) {
            $table->increments('id');            
            $table->string('name');
            $table->integer('max_amount_in_game');
            $table->text('description')->nullable()->default(null);
            $table->text('comment')->nullable()->default(null);
            $table->text('first_message'); //при назначении роли
            //связанные кнопки добавляем через отдельную сущность
            $table->text('kill_message')->nullable()->default(null); //если пытались убить или убили
            $table->boolean('is_select_partner')->default(0); //выбирает кого-то или следит за кем-то
            $table->text('night_message_priv')->nullable()->default(null); //сообщение приходит при наступ. ночи личное
            $table->string('night_action')->nullable()->default(null);
            $table->text('night_message_publ')->nullable()->default(null); //сообщение приходит при наступ. ночи в чат, если роль активна
            $table->text('message_to_partner')->nullable()->default(null); //сообщение тому, к кому пришел
            $table->tinyInteger('role_type_id');
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
        Schema::dropIfExists('game_roles');
    }
}
