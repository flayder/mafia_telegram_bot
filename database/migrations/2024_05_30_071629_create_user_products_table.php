<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->string('user_id',50)->collation('utf8_general_ci');
            $table->string('group_id',50)->collation('utf8_general_ci')->nullable()->default(null); //выбрать при активации
            $table->integer('product_id');  
            $table->timestamp('avail_finish_moment')->nullable()->default(null); //для многоразовых. Когда станет недоступен
            $table->timestamp('was_used')->nullable()->default(null); //когда был использован. если null - значит еще в наличии
            $table->boolean('is_deactivate')->default(0); //сработало уведомление о декативации
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
        Schema::dropIfExists('user_products');
    }
}
