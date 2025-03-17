<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Startnummer extends Model
{
    protected $table = 'startnummern';

    protected $casts = [
        'startnummer' => 'int',
    ];
}
