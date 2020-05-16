<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('starting_point');
            $table->unsignedBigInteger('arrival_point');
            $table->unsignedBigInteger('travel_time');
            $table->float('travel_distance');
            $table->timestamps();

            $table->foreign('starting_point')->references('id')->on('waypoints');
            $table->foreign('arrival_point')->references('id')->on('waypoints');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
}
