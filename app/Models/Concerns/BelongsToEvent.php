<?php

namespace App\Models\Concerns;

use App\Models\Event;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Beschränkt Abfragen auf die aktuelle Veranstaltung und setzt event_id beim Anlegen.
 */
trait BelongsToEvent
{
    public static function bootBelongsToEvent(): void
    {
        static::addGlobalScope('event', function (Builder $builder) {
            if ($id = app(CurrentEvent::class)->id()) {
                $builder->where($builder->qualifyColumn('event_id'), $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->event_id)) {
                $model->event_id = app(CurrentEvent::class)->id();
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeForEvent(Builder $query, Event|int $event): Builder
    {
        return $query->withoutGlobalScope('event')
            ->where($query->qualifyColumn('event_id'), $event instanceof Event ? $event->id : $event);
    }
}
