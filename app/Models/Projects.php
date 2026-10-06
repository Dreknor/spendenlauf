<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Projects extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'projects';

    protected $fillable = ['name', 'description'];

    protected $with = ['media'];

    public function sponsorings(): BelongsToMany
    {
        return $this->belongsToMany(Sponsoring::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_projects', 'projects_id', 'event_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
    }

    /** Beschreibung als Text/Markdown (ältere Einträge enthalten HTML aus TinyMCE). */
    public function beschreibungText(): string
    {
        $text = preg_replace(['~</p>~i', '~<br\s*/?>~i', '~<li>~i'], ["\n\n", "\n", '- '], (string) $this->description);

        return trim(html_entity_decode(strip_tags($text)));
    }

    public function beschreibungHtml(): string
    {
        return Str::markdown($this->beschreibungText(), ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function bildUrl(): ?string
    {
        $media = $this->getFirstMedia('images');

        return $media ? route('image', $media) : null;
    }
}
