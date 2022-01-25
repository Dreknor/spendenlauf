<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Laeufer extends Model
{
    protected $fillable = ['vorname', 'nachname', 'geburtsdatum', 'email', 'verwaltet_von', 'geschlecht', 'startnummer', 'runden'];

    protected $visible = ['vorname', 'nachname', 'geburtsdatum', 'email', 'verwaltet_von', 'geschlecht', 'startnummer', 'runden'];

    protected $dates = ['created_at', 'updated_at', 'geburtsdatum'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
    ];

    /**
     * Accessor for Age.
     */
    public function getAgeAttribute()
    {
        return Carbon::parse($this->attributes['geburtsdatum'])->age;
    }

    public function getNameAttribute($value)
    {
        return $this->vorname.' '.$this->nachname;
    }

    public function besitzer()
    {
        return $this->belongsTo(User::class, 'verwaltet_von');
    }

    public function team()
    {
        return $this->belongsTo(Teams::class, 'team_id');
    }

    /**
     * Get all of the Laeufer sponsorings.
     */
    public function sponsorings()
    {
        return $this->morphMany(Sponsoring::class, 'sponsorable');
    }

    public function laeufer()
    {
        return $this;
    }
}
