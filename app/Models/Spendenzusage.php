<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

/**
 * Noch unbestätigte Spendenzusage aus dem öffentlichen Sponsor-Link. Sie zählt erst, wenn der Sponsor
 * den Link in der Bestätigungsmail öffnet – so kann niemand im Namen fremder Personen Spenden zusagen.
 */
class Spendenzusage extends Model
{
    use MassPrunable;

    public const GUELTIG_TAGE = 7;

    protected $table = 'spendenzusagen';

    protected $fillable = ['daten'];

    protected function casts(): array
    {
        return ['daten' => 'encrypted:array'];
    }

    public function laeufer(): BelongsTo
    {
        return $this->belongsTo(Laeufer::class)->withoutGlobalScope('event');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Die Sponsor-Angaben als (nicht gespeicherter) Datensatz, z. B. für Name und Briefanrede. */
    public function sponsorDaten(): Sponsor
    {
        return new Sponsor(Arr::only($this->daten, (new Sponsor)->getFillable()));
    }

    public function bestaetigungsLink(): string
    {
        return URL::temporarySignedRoute('public.sponsor.bestaetigung', $this->created_at->addDays(self::GUELTIG_TAGE), ['zusage' => $this->id]);
    }

    /** Unbestätigte Zusagen nach Ablauf des Links löschen (php artisan model:prune). */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::GUELTIG_TAGE));
    }
}
