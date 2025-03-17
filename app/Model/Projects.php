<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Projects extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'projects';

    protected $visible = ['name', 'description'];

    protected $fillable = ['name', 'description'];


    protected $with = ['media'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sponsorings()
    {
        return $this->belongsToMany(Sponsoring::class);
    }
}
