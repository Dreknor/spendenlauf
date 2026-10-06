<?php

namespace App\Models;

use App\Enums\MailTyp;
use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Protokoll versandter Mails an Sponsoren (Aufforderung, Erinnerung, Nachweis). */
class SponsorMail extends Model
{
    use BelongsToEvent;

    protected $fillable = ['sponsor_id', 'typ', 'betrag', 'gesendet_am'];

    protected function casts(): array
    {
        return [
            'typ' => MailTyp::class,
            'betrag' => 'decimal:2',
            'gesendet_am' => 'datetime',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }
}
