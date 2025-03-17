<?php

namespace App\Http\Controllers;

use App\Model\Startnummer;
use Illuminate\Http\Request;

class StartnummerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:edit startnummer');
    }

    public function index()
    {
        $startnummern = Startnummer::query()->orderBy('startnummer')->get();

        return view('startnummern.index', [
            'startnummern' => $startnummern,
        ]);
    }

    public function create()
    {
        return view('startnummer.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'startnummer_start' => 'required|integer|min:1',
            'startnummer_ende' => 'required|integer|min:1',
        ]);

        $nummern = [];

        for ($i = $request->startnummer_start; $i <= $request->startnummer_ende; $i++) {
            $nummern[] = [
                'startnummer' => $i,
            ];
        }

        Startnummer::query()->insert($nummern);

        return redirect('startnummern')->with([
            'type' => 'success',
            'message' => 'Startnummern wurde erstellt',
        ]);
    }

    public function destroy(Startnummer $startnummer)
    {
        try {
            $startnummer->delete();
        } catch (\Exception $e) {
            return redirect('startnummern')->with([
                'type' => 'danger',
                'message' => 'Startnummer konnte nicht gelöscht werden',
            ]);
        }

        return redirect('startnummern')->with([
            'type' => 'success',
            'message' => 'Startnummer wurde gelöscht',
        ]);
    }


}
