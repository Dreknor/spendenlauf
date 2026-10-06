<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use App\Services\SpendenRechner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Teams extends Model
{
    use BelongsToEvent, HasFactory;

    protected $fillable = ['name', 'open'];

    protected function casts(): array
    {
        return [
            'open' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => SpendenRechner::vergessen());
        static::deleted(fn () => SpendenRechner::vergessen());
    }

    public function besitzer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verwaltet_von');
    }

    public function laeufer(): HasMany
    {
        return $this->hasMany(Laeufer::class, 'team_id');
    }

    public function sponsorings(): MorphMany
    {
        return $this->morphMany(Sponsoring::class, 'sponsorable');
    }

    public function getRundenAttribute(): int
    {
        return (int) $this->laeufer->sum('runden');
    }

    public function istVerwaltetVon(?User $user): bool
    {
        return $user !== null && (int) $this->verwaltet_von === (int) $user->id;
    }
}
