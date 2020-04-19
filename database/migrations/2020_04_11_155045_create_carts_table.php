<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->bigIncrements('id');
            // 0 : 운행 대기, 1 : 운행 예약, 2 : 운행 중
            // 3 : 이상 차량, 4 : 정비 중
            $table->tinyInteger('status');
            $table->string('cart_location');
            $table->foreign('cart_location')->references('name')->on('waypoints');
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
        Schema::dropIfExists('carts');
    }
}
