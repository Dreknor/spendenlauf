<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sponsoring extends Model
{
    protected $fillable = ['sponsor_id', 'verwaltet_von', 'rundenBetrag', 'festBetrag', 'maxBetrag'];

    protected $visible = ['sponsor_id', 'verwaltet_von', 'rundenBetrag', 'festBetrag', 'maxBetrag'];

    protected $attributes = [
        'rundenBetrag' => '0',
        'festBetrag' => '0',
        'maxBetrag' => '0',
    ];

    public function getTypeAttribute()
    {
        $array = explode('\\', $this->sponsorable_type);

        return $array[2];
    }

    /**
     * Get the owning sponsorable model.
     */
    public function sponsorable()
    {
        return $this->morphTo();
    }

    public function verwalter()
    {
        return $this->belongsTo(User::class, 'verwaltet_von');
    }

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class, 'sponsor_id');
    }

    public function projects()
    {
        return $this->belongsToMany(Projects::class);
    }

    public function spende($Runde = null)
    {
        return $this->getSpendeAttribute($Runde);
    }

    public function getSpendeAttribute($Runde = null)
    {
        if (is_null($Runde)) {
            $Runde = $this->sponsorable->runden;
        }

        $Rundenbetrag = $Runde * $this->rundenBetrag;

        $Summe = $this->festBetrag + $Rundenbetrag;

        if (! is_null($this->maxBetrag)) {
            if ($Summe > $this->maxBetrag) {
                $Summe = $this->maxBetrag;
            }
        }

        return $Summe;
    }
}
