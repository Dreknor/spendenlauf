<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Sponsoren sind veranstaltungsübergreifend; ihre Sponsorings, Zahlungen und Mails
 * werden über den Event-Scope auf die aktuelle Veranstaltung beschränkt.
 */
class Sponsor extends Model
{
    use HasFactory;

    protected $table = 'sponsors';

    protected $fillable = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon'];

    protected static function booted(): void
    {
        static::creating(function (Sponsor $sponsor) {
            $sponsor->uuid ??= (string) Str::uuid();
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('id');
    }

    public function sponsorings(): HasMany
    {
        return $this->hasMany(Sponsoring::class, 'sponsor_id');
    }

    public function zahlungen(): HasMany
    {
        return $this->hasMany(Zahlung::class);
    }

    public function mails(): HasMany
    {
        return $this->hasMany(SponsorMail::class);
    }

    public function getNameAttribute(): string
    {
        $person = trim($this->vorname.' '.$this->nachname);

        if (filled($this->firmenname)) {
            return $person !== '' ? "{$this->firmenname} ($person)" : $this->firmenname;
        }

        return $person;
    }

    public function getAnredeBriefAttribute(): string
    {
        return match (true) {
            filled($this->firmenname) && blank($this->nachname) => 'Sehr geehrte Damen und Herren,',
            $this->anrede === 'Herr' => 'Sehr geehrter Herr '.$this->nachname.',',
            $this->anrede === 'Frau' => 'Sehr geehrte Frau '.$this->nachname.',',
            default => 'Sehr geehrte Damen und Herren,',
        };
    }

    /** @return list<string> */
    public function getAnschriftZeilenAttribute(): array
    {
        return array_values(array_filter([
            $this->firmenname,
            trim(($this->anrede !== 'Firma' ? $this->anrede.' ' : '').$this->vorname.' '.$this->nachname),
            $this->strasse,
            trim($this->plz.' '.$this->ort),
        ], 'filled'));
    }

    /** Spendensumme der aktuellen Veranstaltung in Euro. */
    public function getSpendensummeAttribute(): float
    {
        return $this->sponsorings->sum('spende');
    }

    public function wirdVerwaltetVon(?User $user): bool
    {
        return $user !== null && $this->users->contains('id', $user->id);
    }

    /**
     * Wer den Sponsor zuerst erfasst hat, pflegt seine Kontaktdaten. Weitere Familien, die über
     * E-Mail oder Name + PLZ demselben Sponsor zugeordnet wurden, dürfen nur Spenden eintragen –
     * sonst könnte jeder mit einer bekannten E-Mail-Adresse Anschrift und Mail-Empfänger ändern.
     */
    public function istErfasstVon(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $ersterId = $this->relationLoaded('users')
            ? $this->users->sortBy('pivot.id')->first()?->id
            : $this->users()->orderBy('sponsor_user.id')->value('users.id');

        return (int) $ersterId === (int) $user->id;
    }
}
