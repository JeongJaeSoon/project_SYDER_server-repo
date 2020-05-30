<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            // 정차 상태 => 100 : 배정 완료                 101 : 수신자 동의
            // 대기 상태 => 200 : 출발지 대기 중            201 : 도착지 대기 중
            // 이동 상태 => 300 : 도착지로 차량 이동 중     301 : 출발지로 차량 이동 중
            // 주문 상태 => 400 : 주문 종료                 401 : 주문 취소                 402 : 수신자 거절로 취소
            // 기타 상태 => 900 : 차량 배정 대기
            $table->smallInteger('status');
            $table->unsignedBigInteger('sender');
            $table->unsignedBigInteger('receiver');
            $table->unsignedBigInteger('order_cart')->nullable();
            $table->unsignedBigInteger('order_route');
            $table->boolean('reverse_direction');
            $table->timestamp('request_time');
            $table->timestamp('approved_time')->nullable();
            $table->timestamp('depart_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->timestamps();

            $table->foreign('sender')->references('id')->on('users');
            $table->foreign('receiver')->references('id')->on('users');
            $table->foreign('order_cart')->references('id')->on('carts');
            $table->foreign('order_route')->references('id')->on('routes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
