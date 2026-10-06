<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Veranstaltung (ein Spendenlauf) mit allen Einstellungen.
 *
 * @property CarbonInterface $datum
 */
class Event extends Model
{
    use HasFactory;

    public const STANDARD_ALTERSGRUPPEN = [
        ['von' => null, 'bis' => 6],
        ['von' => 7, 'bis' => 10],
        ['von' => 11, 'bis' => 14],
        ['von' => 15, 'bis' => 18],
        ['von' => 19, 'bis' => 39],
        ['von' => 40, 'bis' => 59],
        ['von' => 60, 'bis' => null],
    ];

    protected $fillable = [
        'name', 'nummer', 'datum', 'anmeldeschluss', 'aktiv',
        'runden_laenge_m', 'dauer_minuten', 'altersgruppen',
        'soziale_abgabe_prozent', 'soziale_abgabe_empfaenger', 'soziale_abgabe_beschreibung', 'klassenkasse_prozent',
        'kontoinhaber', 'iban', 'bic', 'bank', 'paypal_me_name', 'verwendungszweck_prefix', 'zahlungsziel_tage',
        'verein_name', 'verein_anschrift', 'verein_steuernummer', 'verein_finanzamt', 'verein_freistellung_datum', 'verein_zweck',
        'mail_absender_name', 'mail_absender_adresse', 'info_url', 'import_url',
    ];

    protected function casts(): array
    {
        return [
            'datum' => 'date',
            'anmeldeschluss' => 'date',
            'verein_freistellung_datum' => 'date',
            'aktiv' => 'boolean',
            'altersgruppen' => 'array',
            'soziale_abgabe_prozent' => 'decimal:2',
            'klassenkasse_prozent' => 'decimal:2',
        ];
    }

    public function schulklassen(): HasMany
    {
        return $this->hasMany(Schulklasse::class)->orderBy('stufe')->orderBy('name');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Projects::class, 'event_projects', 'event_id', 'projects_id');
    }

    public function laeufer(): HasMany
    {
        return $this->hasMany(Laeufer::class)->withoutGlobalScope('event');
    }

    public function sponsorings(): HasMany
    {
        return $this->hasMany(Sponsoring::class)->withoutGlobalScope('event');
    }

    public function zahlungen(): HasMany
    {
        return $this->hasMany(Zahlung::class)->withoutGlobalScope('event');
    }

    public function istLauftag(): bool
    {
        return $this->datum->isToday();
    }

    /** Anmeldungen und neue Sponsorings sind bis einschließlich Lauftag möglich. */
    public function anmeldungOffen(): bool
    {
        $ende = $this->anmeldeschluss ?? $this->datum;

        return now()->startOfDay()->lte($ende);
    }

    public function laufVorbei(): bool
    {
        return $this->datum->endOfDay()->isPast();
    }

    public function jahr(): int
    {
        return (int) $this->datum->format('Y');
    }

    public function km(int $runden): float
    {
        return round($runden * $this->runden_laenge_m / 1000, 2);
    }

    /**
     * @return list<array{von: ?int, bis: ?int, label: string, key: string}>
     */
    public function altersgruppenListe(): array
    {
        $gruppen = $this->altersgruppen ?: self::STANDARD_ALTERSGRUPPEN;

        return array_map(function (array $g) {
            $von = $g['von'] ?? null;
            $bis = $g['bis'] ?? null;
            $label = match (true) {
                $von === null && $bis !== null => "bis $bis",
                $von !== null && $bis === null => "ab $von",
                default => "{$von}–{$bis}",
            };

            return ['von' => $von, 'bis' => $bis, 'label' => $label.' Jahre', 'key' => ($von ?? 0).'-'.($bis ?? 'x')];
        }, array_values($gruppen));
    }

    public function altersgruppeFuer(?int $alter): array
    {
        if ($alter !== null) {
            foreach ($this->altersgruppenListe() as $gruppe) {
                if (($gruppe['von'] === null || $alter >= $gruppe['von']) && ($gruppe['bis'] === null || $alter <= $gruppe['bis'])) {
                    return $gruppe;
                }
            }
        }

        return ['von' => null, 'bis' => null, 'label' => 'unbekannt', 'key' => 'unbekannt'];
    }

    public function verwendungszweck(Sponsor $sponsor): string
    {
        return trim($this->verwendungszweck_prefix.' '.$this->jahr().' Nr. '.$sponsor->id);
    }

    public function paypalLink(int $betragCent): ?string
    {
        if (blank($this->paypal_me_name) || $betragCent <= 0) {
            return null;
        }

        return 'https://paypal.me/'.rawurlencode($this->paypal_me_name).'/'.number_format($betragCent / 100, 2, '.', '').'EUR';
    }

    public function ibanFormatiert(): ?string
    {
        return $this->iban ? trim(chunk_split(str_replace(' ', '', $this->iban), 4, ' ')) : null;
    }

    public function hatSozialeAbgabe(): bool
    {
        return (float) $this->soziale_abgabe_prozent > 0 && filled($this->soziale_abgabe_empfaenger);
    }

    public function hatKlassenkasse(): bool
    {
        return (float) $this->klassenkasse_prozent > 0;
    }

    public function absenderName(): string
    {
        return $this->mail_absender_name ?: config('mail.from.name');
    }

    public function absenderAdresse(): string
    {
        return $this->mail_absender_adresse ?: config('mail.from.address');
    }
}
