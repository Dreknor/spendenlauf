<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEvent;
use App\Services\SpendenRechner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Zusage eines Sponsors für einen Läufer oder ein Team:
 * Festbetrag + Betrag je Runde, optional gedeckelt durch einen Maximalbetrag.
 */
class Sponsoring extends Model
{
    use BelongsToEvent, HasFactory;

    protected $fillable = ['sponsor_id', 'rundenBetrag', 'festBetrag', 'maxBetrag'];

    protected function casts(): array
    {
        return [
            'rundenBetrag' => 'decimal:2',
            'festBetrag' => 'decimal:2',
            'maxBetrag' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => SpendenRechner::vergessen());
        static::deleted(fn () => SpendenRechner::vergessen());
    }

    public function sponsorable(): MorphTo
    {
        return $this->morphTo();
    }

    public function verwalter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verwaltet_von');
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class, 'sponsor_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Projects::class);
    }

    /**
     * Spenden, die ein Benutzer sehen darf: alle mit „edit sponsorings“, sonst selbst eingetragene
     * sowie Spenden für eigene/mitverwaltete Läufer und eigene Teams.
     */
    public function scopeSichtbarFuer(Builder $query, User $user): Builder
    {
        if ($user->canAny(['edit sponsorings', 'manage abrechnung'])) {
            return $query;
        }

        return $query->where(fn ($q) => $q
            ->where($q->qualifyColumn('verwaltet_von'), $user->id)
            ->orWhereHasMorph('sponsorable', [Laeufer::class], fn ($l) => $l->verwaltetVon($user))
            ->orWhereHasMorph('sponsorable', [Teams::class], fn ($t) => $t->where('verwaltet_von', $user->id)));
    }

    public function istTeamSponsoring(): bool
    {
        return $this->sponsorable_type === 'team';
    }

    /** „Team“ bzw. „Läufer“ */
    public function getTypeAttribute(): string
    {
        return $this->istTeamSponsoring() ? 'Team' : 'Läufer';
    }

    /** Runden, die für dieses Sponsoring zählen (Läufer bzw. Summe aller Teammitglieder). */
    public function getRundenAttribute(): int
    {
        $ziel = $this->sponsorable;

        return $ziel ? (int) $ziel->runden : 0;
    }

    /** Aktueller Spendenbetrag in Euro. */
    public function getSpendeAttribute(): float
    {
        return SpendenRechner::betragCent($this->runden, $this->rundenBetrag, $this->festBetrag, $this->maxBetrag) / 100;
    }
}
