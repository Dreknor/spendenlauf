<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;

/** Pool noch freier Startnummern einer Veranstaltung. */
class Startnummer extends Model
{
    use BelongsToEvent;

    protected $table = 'startnummern';

    protected $fillable = ['startnummer'];

    protected function casts(): array
    {
        return [
            'startnummer' => 'integer',
        ];
    }
}
