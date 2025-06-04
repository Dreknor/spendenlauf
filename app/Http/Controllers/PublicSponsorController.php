<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicSponsorRequest;
use App\Model\Laeufer;
use App\Model\Projects;
use App\Model\Sponsor;
use App\Model\Sponsoring;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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


        try {
            /** @var  $request */
            if ($request->has('firmenname') and !empty($request->firmenname) and $request->anrede = $request->anrede) {
                $sponsor = Sponsor::query()
                    ->where('email', $request->email)
                    ->orWhere(function ($query) use ($request) {
                        $query->where('firmenname', $request->firmenname)
                            ->where('plz', $request->plz)
                            ->where('ort', $request->ort);
                    })->first();
            } else {
                $sponsor = Sponsor::query()
                    ->where('email', $request->email)
                    ->orWhere(function ($query) use ($request) {
                        $query->where('anrede', $request->anrede)
                            ->where('vorname', $request->vorname)
                            ->where('nachname', $request->nachname)
                            ->where('plz', $request->plz)
                            ->where('ort', $request->ort);
                    })->first();
            }

            if (is_null($sponsor)) {
                $sponsor = new Sponsor($request->validated());
                $sponsor->save();
            }

        } catch (\Throwable $th) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.' . $th->getMessage()
            ]);
        }

        try {
            $user = User::firstOrCreate([
                'email' => $request->email,
            ],[
                'vorname' => $request->vorname,
                'nachname'  => $request->nachname,
                'password' => Str::password(32)
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => "Es konnte kein Benutzerkonto für den Sponsor angelegt werden. Bitte versuchen Sie es erneut." . $th->getMessage()
            ]);
        }

        try {
            $sponsoring = new Sponsoring($request->validated());
            $sponsoring->sponsor_id = $sponsor->id;
            $sponsoring->sponsorable_id = $laeufer->id;
            $sponsoring->sponsorable_type = Laeufer::class;
            $sponsoring->verwaltet_von = $user->id;
            $sponsoring->save();
        } catch (\Throwable $th) {
            return redirect()->back()->with([
                'type' => 'danger',
                'Meldung' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.' . $th->getMessage()
            ]);
        }




        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Vielen Dank für Ihre Unterstützung!']);
    }
}
