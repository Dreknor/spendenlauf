<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Einladung eines Kontos (über dessen E-Mail-Adresse), einen Läufer mitzuverwalten.
 * Wirksam erst, wenn die eingeladene Person sie mit einem bestätigten Konto zu dieser Adresse annimmt.
 */
class MitverwaltungEinladung extends Model
{
    use MassPrunable;

    /** Offene Einladungen verfallen nach dieser Zeit. */
    public const GUELTIG_TAGE = 30;

    /** Höchstzahl gleichzeitig offener Einladungen je Läufer (Schutz vor Mail-Spam). */
    public const MAX_OFFEN = 5;

    protected $table = 'mitverwaltung_einladungen';

    protected $fillable = ['laeufer_id', 'email', 'status'];

    protected function casts(): array
    {
        return ['entschieden_am' => 'datetime'];
    }

    public function laeufer(): BelongsTo
    {
        return $this->belongsTo(Laeufer::class)->withoutGlobalScope('event');
    }

    public function einlader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eingeladen_von');
    }

    public function scopeOffen(Builder $query): Builder
    {
        return $query->where('status', MitverwaltungAnfrage::OFFEN)
            ->where('created_at', '>=', now()->subDays(self::GUELTIG_TAGE));
    }

    /** Offene Einladungen an die E-Mail-Adresse des Benutzers. */
    public function scopeFuer(Builder $query, User $user): Builder
    {
        return $query->where('email', self::adresse($user->email));
    }

    public function giltFuer(User $user): bool
    {
        return $this->email === self::adresse($user->email);
    }

    public static function adresse(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** Verfallene Einladungen löschen (php artisan model:prune). */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::GUELTIG_TAGE));
    }
}
