<?php

namespace App\Model;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable /*implements MustVerifyEmail*/
{
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vorname', 'nachname', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getFullNameAttribute($value)
    {
        return $this->vorname.' '.$this->nachname;
    }

    public function getNameAttribute($value)
    {
        return $this->vorname.' '.$this->nachname;
    }

    public function laeufer()
    {
        return $this->hasMany(Laeufer::class, 'verwaltet_von');
    }

    public function sponsorings()
    {
        return $this->hasMany(Sponsoring::class, 'verwaltet_von');
    }

    public function teams()
    {
        return $this->hasMany(Teams::class, 'verwaltet_von');
    }

    public function sponsoren()
    {
        return $this->belongsToMany(Sponsor::class);
    }
}
