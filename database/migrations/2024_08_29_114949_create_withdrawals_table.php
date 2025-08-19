<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('user_id',50);
            $table->text('groups'); //с каких групп вывод
            $table->integer('amount'); //сумма в виндкоинах
            $table->tinyInteger('way'); //куда вывод. 1 - на баланс, 2 - на карту/счет
            $table->tinyInteger('status')->default(0); //0 - создан, 1 - выполнен, 2 - отменен
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
        Schema::dropIfExists('withdrawals');
    }
}
