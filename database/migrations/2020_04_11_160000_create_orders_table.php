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
            // 0 : 배정완료(물건 적재 이전), 1 : 이동 중(물건 적재 이후),
            // 2 : 도착완료(물건 하역 이전), 3 : 운행 완료(물건 하역 이후)
            // 9 : 배정대기(차량 부족으로 인한, 주문 대기)
            $table->smallInteger('order_status');
            $table->unsignedBigInteger('sender');
            $table->foreign('sender')->references('id')->on('users');
            $table->unsignedBigInteger('receiver');
            $table->foreign('receiver')->references('id')->on('users');
            $table->unsignedBigInteger('order_cart');
            $table->foreign('order_cart')->references('id')->on('carts');
            $table->unsignedBigInteger('order_route');
            $table->foreign('order_route')->references('id')->on('routes');
            $table->timestamp('request_time');
            $table->timestamp('approved_time')->nullable();
            $table->timestamp('depart_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
