<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\CurrentEvent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    use AuthorizesRequests;

    protected function event(): Event
    {
        $event = app(CurrentEvent::class)->get();

        abort_if($event === null, 503, 'Es ist noch keine Veranstaltung angelegt.');

        return $event;
    }

    protected function erfolg(string $meldung, ?string $ziel = null): RedirectResponse
    {
        return ($ziel ? redirect($ziel) : back())->with(['type' => 'success', 'status' => $meldung]);
    }

    protected function fehler(string $meldung, ?string $ziel = null): RedirectResponse
    {
        return ($ziel ? redirect($ziel) : back())->withInput()->with(['type' => 'danger', 'status' => $meldung]);
    }
}
