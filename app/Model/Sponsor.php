<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    protected $table = 'sponsors';

    protected $visible = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon', 'id', 'mail_send'];

    protected $fillable = ['anrede', 'vorname', 'nachname', 'firmenname', 'email', 'strasse', 'plz', 'ort', 'telefon', 'mail_send'];

    protected $appends = ['spendensumme'];

    protected $casts = [
        'mail_send' => 'datetime'
    ];


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
            if (!is_null($this->vorname) and !is_null($this->nachname)){
                return  "$this->firmenname ($this->vorname $this->nachname)";
            }
            return  $this->firmenname;
        }

        return $this->vorname.' '.$this->nachname;
    }

    public function getAnredeBriefAttribute($value){
        if (! is_null($this->firmenname)) {
            return 'Sehr geehrte Damen und Herren,';
        }

        if ($this->anrede == "Herr"){
            return 'Sehr geehrter Herr '. $this->nachname.',';
        }

        if ($this->anrede == "Frau"){
            return 'Sehr geehrte Frau '. $this->nachname.',';
        }

        return 'Sehr geehrte Damen und Herren,';
    }

    public function sponsorings()
    {
        return $this->hasMany(Sponsoring::class, 'sponsor_id');
    }

    public function getSpendensummeAttribute()
    {
        return $this->sponsorings->sum('spende');
    }

    public function getSpendenprojectsAttribute()
    {
        $array = [];


        foreach ($this->sponsorings as $sponsoring){
            $sponsoringProjectsArray = $sponsoring->SpendeProjekt;

            foreach ($sponsoringProjectsArray as $key => $value){
                if (array_key_exists($key, $array)){
                    $array[$key] += $value;
                } else {
                    $array[$key] = $value;
                }

            }

        }
        return $array;
    }

}
