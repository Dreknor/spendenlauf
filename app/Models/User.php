<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['vorname', 'nachname', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->vorname.' '.$this->nachname;
    }

    public function laeufer(): HasMany
    {
        return $this->hasMany(Laeufer::class, 'verwaltet_von');
    }

    public function sponsorings(): HasMany
    {
        return $this->hasMany(Sponsoring::class, 'verwaltet_von');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Teams::class, 'verwaltet_von');
    }

    public function sponsoren(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class);
    }
}
