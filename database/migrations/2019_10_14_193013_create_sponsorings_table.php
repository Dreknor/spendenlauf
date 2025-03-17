<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSponsoringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sponsorings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sponsor_id');
            $table->unsignedBigInteger('verwaltet_von');
            $table->morphs('sponsorable');
            $table->decimal('rundenBetrag', 10, 2)->unsigned()->nullable();
            $table->decimal('festBetrag', 10, 2)->unsigned()->nullable();
            $table->decimal('maxBetrag', 10, 2)->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('sponsor_id')->references('id')->on('sponsors')->onDelete('RESTRICT');
            $table->foreign('verwaltet_von')->references('id')->on('users')->onDelete('RESTRICT');
        });

        Schema::create('projects_sponsoring', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('projects_id');
            $table->unsignedBigInteger('sponsoring_id');
            $table->timestamps();

            $table->foreign('projects_id')->references('id')->on('projects')->onDelete('RESTRICT');
            $table->foreign('sponsoring_id')->references('id')->on('sponsorings')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sponsorings');
        Schema::dropIfExists('project_sponsoring');
    }
}
