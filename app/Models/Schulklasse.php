<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schulklasse extends Model
{
    use BelongsToEvent, HasFactory;

    protected $table = 'schulklassen';

    protected $fillable = ['name', 'stufe', 'klassenleitung'];

    protected function casts(): array
    {
        return [
            'stufe' => 'integer',
        ];
    }

    /** Klassen werden auch für nicht aktive Veranstaltungen verwaltet. */
    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('event')->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
    }

    public function laeufer(): HasMany
    {
        return $this->hasMany(Laeufer::class);
    }
}
