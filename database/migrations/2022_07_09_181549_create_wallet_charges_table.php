<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_charges', function (Blueprint $table) {
            $table->increments('id');
            $table->double('pre_mount')->unsigned()->nullable();
            $table->double('new_amount')->unsigned()->nullable();
            $table->double('difference')->unsigned()->nullable();
            $table->integer('user_id')->unsigned();
            $table->integer('wallet_id')->unsigned();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->onDelete('cascade');
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
        Schema::dropIfExists('wallet_charges');
    }
};
