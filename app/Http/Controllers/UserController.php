<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public const RECHTE = [
        'edit user' => 'Benutzer verwalten',
        'edit permission' => 'Rechte vergeben',
        'edit event' => 'Veranstaltungen & Klassen verwalten',
        'edit projekt' => 'Projekte verwalten',
        'edit laeufer' => 'alle Läufer bearbeiten',
        'edit teams' => 'alle Teams bearbeiten',
        'edit sponsoren' => 'alle Sponsoren bearbeiten',
        'edit sponsorings' => 'alle Spenden bearbeiten',
        'edit startnummer' => 'Startnummern verwalten',
        'schnellerfassung' => 'Schnellerfassung am Lauftag',
        'edit runden' => 'Runden eingeben',
        'import export' => 'Import & Export',
        'show auswertung' => 'Statistiken & Auswertungen',
        'manage abrechnung' => 'Abrechnung (Zahlungen, Mails)',
        'send mail' => 'Mails an Sponsoren (alt)',
    ];

    public function index(Request $request)
    {
        $users = User::query()
            ->with('permissions')
            ->withCount('laeufer')
            ->when($request->filled('q'), function ($q) use ($request) {
                $suche = '%'.$request->input('q').'%';
                $q->where(fn ($q) => $q->where('vorname', 'like', $suche)->orWhere('nachname', 'like', $suche)->orWhere('email', 'like', $suche));
            })
            ->when($request->input('filter') === 'team', fn ($q) => $q->has('permissions'))
            ->orderBy('nachname')
            ->paginate(50)
            ->withQueryString();

        return view('users.index', ['users' => $users]);
    }

    public function show(User $user)
    {
        return view('users.show', [
            'user' => $user->load('permissions'),
            'rechte' => collect(self::RECHTE)->only(Permission::pluck('name')),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $daten = $request->validate([
            'vorname' => ['required', 'string', 'max:255'],
            'nachname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->fill(collect($daten)->only(['vorname', 'nachname', 'email'])->all())->save();

        if ($request->user()->can('edit permission')) {
            $user->syncPermissions($daten['permissions'] ?? []);
        }

        return $this->erfolg('Benutzer gespeichert.');
    }

    public function sendVerification(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return $this->fehler('Die E-Mail-Adresse ist bereits bestätigt.');
        }

        $user->sendEmailVerificationNotification();

        return $this->erfolg('Bestätigungs-Mail wurde versandt.');
    }
}
