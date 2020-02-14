@extends('layouts.app')

@section('content')

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
                                {{$Sponsoren}} {{__('Spender')}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="row justify-content-center">
        <div class="col">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">
                        Willkommen
                    </h5>
                </div>
                @if(!auth()->user()->hasVerifiedEmail())
                    <div class="card-body bg-warning">
                        <p>
                            {{__('Ihre E-Mail-Adresse muss zunächste bestätigt werden. Bitte nutzen Sie den Link aus der versendeten E-Mail.')}}
                        </p>
                        <p>
                            <a href="user/{{auth()->user()->id}}/sendVerification" class="btn btn-info">{{__('E-Mail erneut zusenden')}}</a>
                        </p>
                    </div>
                @endif
                <div class="card-body">
                    <p>
                        Schön, dass sie die Projekte in und für Radebeul unterstützen. Sie können sich im Menüpunkt "Projekte" eine Übersicht über die unterstützten projekte verschaffen.
                    </p>
                    <p>
                        Bitte gehen Sie wie folgt vor:
                    </p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <b>
                                Schritt 1:
                            </b>
                        </div>
                        <div class="col-md-4 col-sm-8">
                            <a href="{{url('/laeufer')}}" class="btn  btn-block btn-outline-info">
                                <i class="fas fa-running"></i>
                                {{__('Läufer anmelden')}}
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <b>
                                Schritt 2 (optional):
                            </b>
                        </div>
                        <div class="col-md-4 col-sm-8">
                            <a href="{{url('/teams')}}" class="btn btn-block btn-outline-info">
                                <i class="fas fa-user-friends"></i>
                                {{__('Teams anlegen und Läufer eintragen')}}
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <b>
                                Schritt 3:
                            </b>
                        </div>
                        <div class="col-md-4 col-sm-8">
                            <a href="{{url('/sponsoren')}}" class="btn btn-block  btn-outline-info">
                                <i class="fas fa-hand-holding-usd"></i>
                                {{__('Spender erfassen')}}
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-sm-4">
                            <b>
                                Schritt 4:
                            </b>
                        </div>
                        <div class="col-md-4 col-sm-8">
                                <a href="{{url('/sponsorings')}}" class="btn btn-block  btn-outline-info">
                                    <i class="fas fa-euro-sign"></i>
                                    {{__('Spenden erstellen')}}
                                </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
