<?php

namespace App\Models;

use App\Enums\Kategorie;
use App\Models\Concerns\BelongsToEvent;
use App\Services\SpendenRechner;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Laeufer extends Model
{
    use BelongsToEvent, HasFactory, SoftDeletes;

    protected $fillable = [
        'vorname', 'nachname', 'geburtsdatum', 'email', 'geschlecht', 'kategorie', 'schulklasse_id',
        'startnummer', 'team_id', 'runden',
    ];

    protected function casts(): array
    {
        return [
            'geburtsdatum' => 'date',
            'kategorie' => Kategorie::class,
            'runden' => 'integer',
            'startnummer' => 'integer',
            'runden_manuell' => 'boolean',
            'runden_aktualisiert_am' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Laeufer $laeufer) {
            $laeufer->uuid ??= (string) Str::uuid();
        });

        static::saved(fn () => SpendenRechner::vergessen());
        static::deleted(fn () => SpendenRechner::vergessen());
    }

    /** Alter am Lauftag der Veranstaltung (bzw. heute, falls kein Event bekannt). */
    public function getAgeAttribute(): ?int
    {
        if (! $this->geburtsdatum) {
            return null;
        }

        $aktuell = app(CurrentEvent::class)->get();
        $stichtag = $aktuell && $aktuell->id === $this->event_id
            ? $aktuell->datum
            : ($this->event?->datum ?? now());

        return (int) $this->geburtsdatum->diffInYears($stichtag);
    }

    public function getNameAttribute(): string
    {
        return $this->vorname.' '.$this->nachname;
    }

    /** Für öffentliche Anzeigen: „Max M.“ */
    public function getKurznameAttribute(): string
    {
        return $this->vorname.' '.mb_substr($this->nachname, 0, 1).'.';
    }

    public function istSchueler(): bool
    {
        return $this->kategorie === Kategorie::Schueler;
    }

    public function besitzer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verwaltet_von');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }

    public function schulklasse(): BelongsTo
    {
        return $this->belongsTo(Schulklasse::class);
    }

    public function sponsorings(): MorphMany
    {
        return $this->morphMany(Sponsoring::class, 'sponsorable');
    }

    public function istVerwaltetVon(?User $user): bool
    {
        return $user !== null && (int) $this->verwaltet_von === (int) $user->id;
    }
}
