<?php

namespace App\Support;

use App\Models\Event;

/**
 * Liefert die Veranstaltung, auf die sich die Anwendung gerade bezieht:
 * Admins (Recht „edit event“) können per Session ein anderes Event wählen,
 * alle anderen sehen das aktive Event.
 */
class CurrentEvent
{
    public const SESSION_KEY = 'event_id';

    private ?Event $event = null;

    private bool $resolved = false;

    public function get(): ?Event
    {
        if (! $this->resolved) {
            $this->event = $this->resolve();
            $this->resolved = true;
        }

        return $this->event;
    }

    public function id(): ?int
    {
        return $this->get()?->id;
    }

    public function set(?Event $event): void
    {
        $this->event = $event;
        $this->resolved = true;
    }

    public function forget(): void
    {
        $this->event = null;
        $this->resolved = false;
    }

    private function resolve(): ?Event
    {
        $request = request();

        if ($request->hasSession() && ($id = $request->session()->get(self::SESSION_KEY))) {
            $user = $request->user();
            if ($user && $user->can('edit event') && ($event = Event::find($id))) {
                return $event;
            }
        }

        return Event::query()->where('aktiv', true)->first()
            ?? Event::query()->orderByDesc('datum')->first();
    }
}
