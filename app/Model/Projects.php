<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Projects extends Model implements HasMedia
{
    use HasMediaTrait;

    protected $table = 'projects';

    protected $visible = ['name', 'description'];

    protected $fillable = ['name', 'description'];

    protected $dates = ['created_at', 'updated_at'];

    protected $with = ['media'];

    public function sponsorings()
    {
        return $this->belongsToMany(Sponsoring::class);
    }
}
