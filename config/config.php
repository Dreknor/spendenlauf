<?php

use Carbon\Carbon;

return [
    "spendenlauf" => [
        'date' => (env('DATE')!="")? Carbon::createFromFormat('Y-m-d', env('DATE')) : Carbon::now(),
    ],

    'logo' => [
        'logo' => env('LOGO', 'img/logo.png'),
        'link' => env('LOGO_LINK', config('app.url'))
    ]


    ];
