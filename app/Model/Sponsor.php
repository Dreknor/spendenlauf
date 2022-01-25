<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    protected $table = 'sponsors';

    protected $visible = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon', 'id'];

    protected $fillable = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon'];

    protected $appends = ['spendensumme'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function verwalter()
    {
        return $this->belongsToMany(User::class);
    }

    public function getNameAttribute($value)
    {
        if (! is_null($this->firmenname)) {
            return  "$this->firmenname ($this->vorname $this->nachname)";
        }

        return $this->vorname.' '.$this->nachname;
    }

    public function sponsorings()
    {
        return $this->hasMany(Sponsoring::class, 'sponsor_id');
    }

    public function getSpendensummeAttribute()
    {
        return $this->sponsorings->sum('spende');
    }
}
