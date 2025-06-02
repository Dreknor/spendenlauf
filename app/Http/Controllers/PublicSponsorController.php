<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicSponsorRequest;
use App\Model\Laeufer;
use App\Model\Projects;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use Illuminate\Http\Request;

class PublicSponsorController extends Controller
{
    public function show($uuid)
    {
        $laeufer = Laeufer::where('uuid', $uuid)->firstOrFail();

        return view('public.laeufer.show', [
            'laeufer' => $laeufer,
            'projekte' => Projects::all()
        ]);
    }

    public function store(PublicSponsorRequest $request, $uuid)
    {
        $laeufer = Laeufer::where('uuid', $uuid)->firstOrFail();

        $sponsor = Sponsor::create($request->validated());

        $sponsoring = new Sponsoring();
        $sponsoring->sponsor_id = $sponsor->id;
        $sponsoring->sponsorable_id = $laeufer->id;
        $sponsoring->sponsorable_type = Laeufer::class;
        $sponsoring->spende = $request->spende;
        $sponsoring->save();

        return redirect()->back()->with('success', 'Vielen Dank für Ihre Unterstützung!');
    }
}
