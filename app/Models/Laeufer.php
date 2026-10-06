<?php

namespace App\Models;

use App\Enums\Kategorie;
use App\Models\Concerns\BelongsToEvent;
use App\Services\SpendenRechner;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** Weitere Benutzer (z. B. zweiter Elternteil), die den Läufer mitverwalten. */
    public function mitverwalter(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function mitverwaltungAnfragen(): HasMany
    {
        return $this->hasMany(MitverwaltungAnfrage::class);
    }

    /** Läufer, die der Benutzer angemeldet hat oder mitverwaltet. */
    public function scopeVerwaltetVon(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q
            ->where($q->qualifyColumn('verwaltet_von'), $user->id)
            ->orWhereHas('mitverwalter', fn ($m) => $m->whereKey($user->id)));
    }

    public function istVerwaltetVon(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (int) $this->verwaltet_von === (int) $user->id
            || ($this->relationLoaded('mitverwalter')
                ? $this->mitverwalter->contains('id', $user->id)
                : $this->mitverwalter()->whereKey($user->id)->exists());
    }
}
