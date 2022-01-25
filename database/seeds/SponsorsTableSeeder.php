<?php

use Illuminate\Database\Seeder;

class SponsorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        //factory(\App\Model\User::class, 20)->create();

        \App\Model\Laeufer::truncate();
        \App\Model\Sponsor::truncate();
        \App\Model\Sponsoring::truncate();
        factory(\App\Model\Laeufer::class, 270)->create();
        factory(\App\Model\Sponsor::class, 215)->create();
        factory(\App\Model\Sponsoring::class, 280)->create()->each(function ($sponsoring) {
            for ($x = 0; $x < rand(1, 5); $x++) {
                $sponsoring->projects()->save(\App\Model\Projects::find(rand(4, 8)))->make();
            }
        });
        factory(\App\Model\Teams::class, 55)->create();

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
