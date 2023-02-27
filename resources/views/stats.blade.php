<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{asset('img/favicon.ico')}}" type="image/x-icon">

    <title>{{ config('app.name') }}</title>


    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/paper-dashboard.css?v=2.0.0')}}" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />


    <link href="{{asset('/css/all.css')}}" rel="stylesheet">
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />

    <script src="{{asset('js/core/jquery.min.js')}}"></script>
    <script src="{{asset('js/core/popper.min.js')}}"></script>
    <script src="{{asset('js/core/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/plugins/perfect-scrollbar.jquery.min.js')}}"></script>


    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>

</head>

<body id="app-layout">



    <div class="container-fluid">
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
                                    {{$Laeufer}} {{__('Läufer')}} (aktive: {{$aktiveLaeufer}})
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
                                    {{$Sponsoren}} {{__('Spender')}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="icon-big icon-warning text-center text-info">
                                    <i class="fa fa-sync fa-3x"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="numbers text-info">
                                    @if($gesamtRunden > 0 and $aktiveLaeufer > 0)
                                         {{$gesamtRunden}} {{__('Runden')}} (Ø {{round($gesamtRunden/ $aktiveLaeufer, 1)}})
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card border border-info">
                <div class="card-header">
                    <h5>
                        {{__('Auswertung Läufer')}}
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped" id="laeuferTable">
                        <thead>
                        <tr>
                            <th>Platz</th>
                            <th class="no-sort">Startnummer</th>
                            <th class="text-right">Runden</th>
                            <th class="text-right">Altersgruppe</th>
                            <th class="text-right">Spendensumme (nur Sponsoring Läufer)</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($laeufers as $laeufer)
                            <tr>
                                <td>

                                </td>
                                <td>
                                    {{$laeufer->startnummer}}
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
        </div><div class="row">
            <div class="col-12">
                <div class="card border border-info">
                <div class="card-header">
                    <h5>
                        {{__('Auswertung Teams')}}
                    </h5>
                </div>
                    <div class="card-body border-top">
                        <div class="card-body">
                            <table class="table table-striped" id="teamsTable">
                                            <thead >
                                            <tr >
                                                <th>
                                                    Platz
                                                </th>
                                                <th class="no-sort">
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
                                            @foreach($teams as $team)
                                                <tr>
                                                    <td></td>
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
    </div>


<script type="text/javascript">
    $(document).ready(function () {
        var t = $('#laeuferTable').DataTable({
            columnDefs: [
                {
                    searchable: false,
                    orderable: false,
                    targets: 0,
                },
                { targets: 'no-sort', orderable: false }

            ],
            order: [[2, 'desc']],
        });

        t.on('order.dt search.dt', function () {
            let i = 1;

            t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                this.data(i++);
            });
        }).draw();

        var teamsTable = $('#teamsTable').DataTable({
            columnDefs: [
                {
                    searchable: false,
                    orderable: false,
                    targets: 0,
                },
                { targets: 'no-sort', orderable: false }

            ],
            order: [[2, 'desc']],
        });

        teamsTable.on('order.dt search.dt', function () {
            let teamsCounter = 1;

            teamsTable.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                this.data(teamsCounter++);
            });
        }).draw();
    });
</script>
</body>
</html>
