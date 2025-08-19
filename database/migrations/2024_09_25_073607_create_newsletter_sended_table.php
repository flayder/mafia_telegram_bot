<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsletterSendedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('newsletter_sended', function (Blueprint $table) {
            $table->id();
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->bigInteger('newsletter_id');
            $table->tinyInteger('status');  //1 - успешно, 2 - ошибка
            $table->string('error',2000)->nullable()->default(null);
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
        Schema::dropIfExists('newsletter_sended');
    }
}
