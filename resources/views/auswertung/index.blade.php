@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-info">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <h5 class="card-title text-white">
                            {{__('Auswertung')}}
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="icon-big icon-warning text-center">
                                            <i class="fas fa-hand-holding-usd fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="numbers">
                                            {{number_format($Spenden,2)}} € {{__('Spendenerlös')}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="icon-big icon-warning text-center text-info">
                                            <i class="fas fa-running fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="numbers text-info">
                                            {{$Laeufer}} {{__('Läufer')}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card text-warning">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="icon-big icon-warning text-center">
                                            <i class="fas fa-user-friends fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="numbers">
                                            {{$Teams}} {{__('Teams')}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card text-danger">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="icon-big icon-warning text-center">
                                            <i class="fas fa-hand-holding-usd fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="numbers">
                                            {{$SponsorenCount}} {{__('Spender')}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body bg-info">
                <p>
                    Die Auswertung der Läufer und Teams erfolgt über die untenstehende Tabellen. Die Tabellen sind nach Rundenanzahl in der ersten Ebene und Spendenbetrag in der 2. Ebene sortiert.
                    Um eine andere Sortierung zu erreichen muss im Spalten-Kopf geklickt werden. Für eine weitere Sortierungsebene einfach die Shift-Taste gedrückt lassen und weitere Spalte anklicken.
                    <br>
                    Um ein Filtern nach Altersgruppe durchzuführen muss einfach die entsprechende Altersgruppe in das Suchfeld eingegeben werden. Es stehen folgende Altersgruppen zur Verfügung:<br>
                </p>
                <ul>
                    <li>0-5</li>
                    <li>6-10</li>
                    <li>11-14</li>
                    <li>14-18</li>
                    <li>19-30</li>
                    <li>31-40</li>
                    <li>41-50</li>
                    <li>51-60</li>
                    <li>61-70</li>
                    <li>ab 71</li>
                </ul>
                </ul>

                </p>
            </div>
            <div class="card-body border-top">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    {{__('Auswertung Läufer')}}
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th class="text-right">Runden</th>
                                            <th class="text-right">Altersgruppe</th>
                                            <th class="text-right">Spendensumme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($laeuferRunden as $laeufer)
                                        <tr>
                                            <td>
                                                {{$laeufer->name}}
                                            </td>
                                            <td class="text-right">
                                                {{$laeufer->runden}}
                                            </td>
                                            <td class="text-right">
                                                @if($laeufer->age == null)
                                                    keine Angabe
                                                @elseif($laeufer->age < 6)
                                                  0-5
                                                @elseif($laeufer->age >=6 and $laeufer->age <= 10)
                                                  6-10
                                                @elseif($laeufer->age >10 and $laeufer->age <= 14)
                                                  11-14
                                                @elseif($laeufer->age >14 and $laeufer->age <= 18)
                                                    14-18
                                                @elseif($laeufer->age >18 and $laeufer->age <= 30)
                                                    19-30
                                                @elseif($laeufer->age >30 and $laeufer->age <= 40)
                                                    31-40
                                                @elseif($laeufer->age >40 and $laeufer->age <= 50)
                                                    41-50
                                                @elseif($laeufer->age >50 and $laeufer->age <= 60)
                                                    51-60
                                                @elseif($laeufer->age >60 and $laeufer->age <= 70)
                                                    61-70
                                                @elseif($laeufer->age >70)
                                                    ab 71
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                {{number_format($laeufer->sponsorings->sum('spende'),2)}} €
                                            </td>

                                        </tr>
                                    @endforeach
                                    </tbody>

                                </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    {{__('Auswertung Teams')}}
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead >
                                        <tr >
                                            <th>
                                                    {{__('Name')}}
                                            </th>
                                            <th class="text-right">
                                                    {{__('Runden')}}
                                            </th>
                                            <th class="text-right">
                                                    {{__('Läufer')}}
                                            </th>
                                            <th class="text-right">
                                                    {{__('Spendensumme')}}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($teamsRunden as $team)
                                        <tr>
                                                <td class="">
                                                    {{$team->name}}
                                                </td>
                                                <td class="text-right">
                                                    {{$team->runden}}
                                                </td>
                                                <td class="text-right">
                                                    {{$team->laeufer->count()}}
                                                </td>
                                                <td class="text-right">
                                                    {{number_format($team->sponsorings->sum('spende'),2)}} €
                                                </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>
                                    {{__('Auswertung Sponsoren')}}
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-striped" id="sponsortable">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th></th>
                                        <th class="text-right">Sponsorings</th>
                                        <th class="text-right">Spendensumme</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($sponsoren as $sponsor)
                                        <tr>
                                            <td>
                                                {{$sponsor->name}}
                                            </td>
                                            <td></td>
                                            <td class="text-right">
                                                {{$sponsor->sponsorings->count()}}
                                            </td>
                                            <td class="text-right">
                                                {{number_format($sponsor->spendensumme,2)}} €
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready( function () {
            $('table').DataTable({
                "order": [[ 1, 'desc' ], [ 3, 'asc' ]],
            });
        } );
    </script>

@endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />

@endsection
