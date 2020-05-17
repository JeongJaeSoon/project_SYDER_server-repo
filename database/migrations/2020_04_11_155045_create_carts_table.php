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
            // 110 : 미배정 차량                 111 : 운행 예약
            // 210 : 출발지 대기 중              211 : 도착지 대기 중
            // 310 : 도착지로 차량 이동 중       311 : 출발지로 차량 이동 중
            // 910 : 차량 이상 발생
            $table->smallInteger('status');
            $table->unsignedBigInteger('cart_location');
            $table->foreign('cart_location')->references('id')->on('waypoints');
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
