<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrincipalTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipos', function (Blueprint $table) {
          $table->increments('id');
          $table->string('name');
          $table->string('name_trans');
          $table->string('icon');
        });

        Schema::create('inputs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_trans')->nullable();
            $table->tinyInteger('sexo')->default(0);
            $table->integer('tipo_id')->unsigned()->nullable();
            $table->foreign('tipo_id')->references('id')->on('tipos');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->tinyInteger('generico')->default(0);
            $table->timestamps();
        });

        Schema::create('outputs', function (Blueprint $table) {
            $table->increments('id');
            $table->text('frase');
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
        Schema::dropIfExists('inputs');
        Schema::dropIfExists('outputs');
        Schema::dropIfExists('tipos');
    }
}
