<?php

namespace App\Models;

use App\Enums\ZahlungsArt;
use App\Models\Concerns\BelongsToEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zahlung extends Model
{
    use BelongsToEvent, HasFactory;

    protected $table = 'zahlungen';

    protected $fillable = ['sponsor_id', 'betrag', 'bezahlt_am', 'art', 'notiz'];

    protected function casts(): array
    {
        return [
            'betrag' => 'decimal:2',
            'bezahlt_am' => 'date',
            'art' => ZahlungsArt::class,
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function erfasser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'erfasst_von');
    }
}
