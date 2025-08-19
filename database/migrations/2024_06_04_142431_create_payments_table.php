<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('offer_id'); // за что платим
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->decimal('amount',10,2);
            $table->string('currency',20)->collation('utf8_general_ci')->default('RUB');
            $table->string('pay_method')->collation('utf8_general_ci')->default('freekassa');
            $table->tinyInteger('status')->default(0);  //1 - успешно оплачен, 2 - отказ, 0 - новый
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
        Schema::dropIfExists('payments');
    }
}
