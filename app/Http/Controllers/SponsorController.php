<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateSponsorRequest;
use App\Mail\SponsorAnschreiben;
use App\Model\Laeufer;
use App\Model\Sponsor;
use App\Model\Teams;
use App\Repositories\SpendenlaufRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class SponsorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (auth()->user()->can('edit sponsoren')) {
            $sponsoren = Sponsor::query()->orderBy('nachname')->get();
        } else {
            $sponsoren = auth()->user()->sponsoren()->orderBy('nachname')->paginate(20);
        }

        $sponsoren->load(['sponsorings', 'verwalter']);

        return view('sponsoren.index', [
            'sponsoren'   => $sponsoren,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sponsoren.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSponsorRequest $request)
    {
        $sponsor = Sponsor::firstOrNew([
            'vorname'  => $request->vorname,
            'nachname'  => $request->nachname,
            'ort'  => $request->ort,
            'plz'  => $request->plz,
            'strasse'  => $request->strasse,
        ], $request->all());

        $sponsor->save();

        $sponsor->users()->syncWithoutDetaching(auth()->user());

        return redirect(url('sponsoren'))->with([
            'type'   => 'success',
            'Meldung'  => __('Spender angelegt'),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Sponsor  $sponsor
     * @return \Illuminate\Http\Response
     */
    public function show(Sponsor $sponsor)
    {
        return $this->edit($sponsor);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Sponsor  $sponsor
     * @return \Illuminate\Http\Response
     */
    public function edit($sponsor)
    {
        $sponsor = Sponsor::find($sponsor);

        if (! auth()->user()->can('edit sponsoren') and ! auth()->user()->sponsoren->contains($sponsor)) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        $sponsor->load(['sponsorings', 'sponsorings.projects', 'sponsorings.projects.media', 'sponsorings.sponsorable', 'sponsorings.sponsor']);

        return view('sponsoren.edit', [
            'sponsor'    =>$sponsor,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Sponsor  $sponsor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $sponsor)
    {
        $sponsor = Sponsor::find($sponsor);
        if ($sponsor->update($request->all())) {
            return redirect()->back()->with([
                'type'   => 'success',
                'Meldung'  => __('Spender bearbeitet'),
            ]);
        }

        return redirect()->back()->with([
            'type'   => 'danger',
            'Meldung'  => __('Speichern fehlgeschlagen'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Sponsor  $sponsor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sponsor $sponsor)
    {
        //
    }

    public function sendMail($Sponsor)
    {
        if (! auth()->user()->can('send mail')) {
            return redirect()->back()->with([
                'type'   => 'danger',
                'Meldung'  => __('Berechtigung fehlt'),
            ]);
        }

        if (Carbon::now()->lessThan(Carbon::createFromFormat('d.m.Y', '15.06.2019'))) {
            return redirect()->back()->with([
                'type'   => 'danger',
                'Meldung'  => __('Erst ab 15.06.2020 möglich'),
            ]);
        }

        if ($Sponsor == 'all') {
            $sponsors = Sponsor::all();
        } else {
            $sponsors = Sponsor::where('id', $Sponsor)->get();
        }

        $sponsors->load(['sponsorings', 'sponsorings.sponsorable', 'sponsorings.projects']);

        $repository = new SpendenlaufRepository();
        $zaehler = 0;

        foreach ($sponsors as $sponsor) {
            if (! is_null($sponsor->email) and $sponsor->sponsorings->count() > 0) {
                $zaehler++;
                Mail::to($sponsor->email)->queue(new SponsorAnschreiben($sponsor, $repository->anzahlLauefer(), $repository->spendensumme()));
            }
        }

        return redirect()->back()->with([
            'type'=>'success',
            'Meldung' => __("Es wurden $zaehler Mails versandt"),
        ]);
    }
}
