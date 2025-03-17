<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLaeufersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laeufers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('verwaltet_von');
            $table->string('vorname');
            $table->string('nachname');
            $table->string('email')->nullable();
            $table->date('geburtsdatum');
            $table->boolean('geschlecht');
            $table->integer('startnummer')->unique();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->integer('runden')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('verwaltet_von')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('laeufers');
    }
}
