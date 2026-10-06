<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anfrage eines Benutzers, einen bereits angemeldeten Läufer mitzuverwalten. */
class MitverwaltungAnfrage extends Model
{
    public const OFFEN = 'offen';

    public const ANGENOMMEN = 'angenommen';

    public const ABGELEHNT = 'abgelehnt';

    protected $table = 'mitverwaltung_anfragen';

    protected $fillable = ['laeufer_id', 'user_id', 'status'];

    protected function casts(): array
    {
        return ['entschieden_am' => 'datetime'];
    }

    public function laeufer(): BelongsTo
    {
        return $this->belongsTo(Laeufer::class)->withoutGlobalScope('event');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
