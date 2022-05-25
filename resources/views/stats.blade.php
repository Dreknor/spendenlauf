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
            <div class="col-md-4 col-sm-6">
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
                                    {{$gesamtRunden}} {{__('Runden')}} (Ø {{round($gesamtRunden/ $aktiveLaeufer, 1)}})
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
                                    bester Läufer: {{$besterLaeufer->runden}} Runden
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
                                    bestes Team: {{$bestesTeam}} {{__('Runden')}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>
</html>
