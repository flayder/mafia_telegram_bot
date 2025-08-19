<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',150); //предложение (надпись на кнопке)
            $table->decimal('price'); //цена в $
            $table->string('product',100); //строковый код продукта. Изначально название валюты
            $table->integer('product_amount'); //количество продукта
            $table->string('where_access')->default('allways'); //когда доступно  (перечислить месяцы через запятую)
            $table->integer('parent_id');
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
        Schema::dropIfExists('offers');
    }
}
