<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
    protected $visible = ['name', 'open', 'verwaltet_von'];

    protected $fillable = ['name', 'open', 'verwaltet_von'];

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'open'  => 'boolean',
    ];

    protected $with = ['laeufer'];

    public function besitzer()
    {
        return $this->belongsTo(User::class, 'verwaltet_von');
    }

    public function laeufer()
    {
        return $this->hasMany(Laeufer::class, 'team_id');
    }

    /**
     * Get all of the Team sponsorings.
     */
    public function sponsorings()
    {
        return $this->morphMany(Sponsoring::class, 'sponsorable');
    }

    public function getRundenAttribute()
    {
        //$Laeufer = $this->hasMany(Laeufer::class, 'team_id');
        return $this->laeufer->sum('runden');
    }
}
