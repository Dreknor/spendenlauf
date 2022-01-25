<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSponsorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sponsors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('anrede');
            $table->string('vorname');
            $table->string('nachname');
            $table->string('firmenname')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('strasse');
            $table->string('plz');
            $table->string('ort');
            $table->string('telefon')->nullable();
            $table->timestamps();
        });

        Schema::create('sponsor_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sponsor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('sponsor_user');
    }
}
