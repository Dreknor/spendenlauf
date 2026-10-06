<?php

use App\Models\Event;
use App\Models\User;
use App\Support\CurrentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/** Legt ein aktives Event an und setzt es als aktuelles Event. */
function aktivesEvent(array $attribute = []): Event
{
    $event = Event::factory()->create($attribute);
    app(CurrentEvent::class)->set($event);

    return $event;
}

/** Benutzer mit allen Rechten. */
function admin(): User
{
    return User::factory()->create()->givePermissionTo(Permission::pluck('name'));
}
