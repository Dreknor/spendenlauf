@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title">
                    {{__('neue Spende erstellen')}}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{url('sponsorings')}}" class="form-horizontal" method="post">
                    @csrf
                    <div class="form-row">
                        <label for="type">
                            {{__('Spende für Team oder Läufer?')}}
                        </label>
                        <select class="custom-select" name="type" id="sponsoringType" required>
                            <option disabled selected>{{__('bitte wählen')}}</option>
                            <option value="Team">{{__('Team')}}</option>
                            <option value="Laeufer">{{__('Einzelläufer')}}</option>
                        </select>
                    </div>
                    <div class="form-row d-none" id="LaeuferOption">
                        <label for="laeufernamen" class="label">{{__('Bitte Läufer auswählen')}}</label>
                        <select class="custom-select empfaenger" name="laeufer"  >
                            <option disabled selected>{{__('bitte wählen')}}</option>
                        @foreach($laeufers as $laeufer)
                                <option value="{{$laeufer->id}}">{{$laeufer->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row d-none" id="TeamOption">
                        <label for="team" class="label">{{__('Bitte Team auswählen')}}</label>
                        <select class="custom-select empfaenger" name="team" id="team"  >
                            <option disabled selected>{{__('bitte wählen')}}</option>
                            @foreach($teams as $team)
                                <option value="{{$team->id}}">{{$team->name}} @if($team->verwaltet_von != auth()->user()->id)(öffentliches Team - {{$team->laeufer->count()}} Läufer)@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row d-none" id="sponsorSelect">
                        <label for="selectSponsor" class="label">{{__('Bitte Spender auswählen')}}</label>
                        <select class="custom-select empfaenger" name="sponsor" id="selectSponsor" >
                            <option disabled selected>{{__('bitte wählen')}}</option>
                            @foreach($sponsors as $sponsor)
                                <option value="{{$sponsor->id}}">{{$sponsor->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row mt-3 d-none sponsoring" id="betraege">
                        <div class="row">
                            <div class="col-md-4 col-sm-12">
                                <label for="festBetrag" class="label">{{__('Festbetrag (in €)')}}</label>
                                <input class="form-control moneyInput" type="number" name="festBetrag" min="0" step="0.10" id="festBetrag">
                            </div>

                            <div class="col-md-4 col-sm-12">
                                <label for="rundenBetrag" class="label">{{__('Betrag je Runde (in €)')}}</label>
                                <input class="form-control moneyInput" type="number" name="rundenBetrag" min="0" step="0.10" id="rundenBetrag">
                            </div>

                            <div class="col-md-4 col-sm-12">
                                <label for="maxBetrag" class="label">{{__('maximaler Betrag (Festbetrag + Rundenbetrag - in €)')}}</label>
                                <input class="form-control moneyInput" type="number" name="maxBetrag" min="0" step="0.10" id="maxBetrag">
                            </div>
                        </div>
                    </div>
                    <div class="form-row mt-3 pl-2 d-none sponsoring" >
                        <div class="alert alert-info">
                            <p>{{__('Es können ein oder mehrere Projekte ausgewählt werden. Wird kein Projekt ausgewählt, wird der Spendenbetrag auf alle Projekte aufgeteilt.')}}</p>
                        </div>
                        <label class="label" for="projects">{{__('Welche Projekte sollen gesponsert werden?')}}</label>
                        @foreach($projects as $project)
                            <div class="">
                                <label class="form-check-label">
                                    <input type="checkbox" id="{{$project->id}}" name="projects[]" value="{{$project->id}}">
                                    {{$project->name}}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-row">
                        <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">{{__('Spende anlegen')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection

@push('js')
    <script>
        $('#sponsoringType').change(function(){
            $('#sponsoringType option:selected').each(function(){
                if($(this).val() == "Team"){
                    $('#TeamOption').removeClass('d-none');
                    $('#TeamOption').addClass('d-block');
                    $('#LaeuferOption').addClass('d-none');
                    $('#LaeuferOption').removeClass('d-block');

                } else if($(this).val() == "Laeufer") {
                    $('#LaeuferOption').removeClass('d-none');
                    $('#LaeuferOption').addClass('d-block');

                    $('#TeamOption').addClass('d-none');
                    $('#TeamOption').removeClass('d-block');
                }
            });
        });

        $('.empfaenger').change(function(){
            $('.empfaenger option:selected').each(function(){
                $('#sponsorSelect').removeClass('d-none');
                $('#sponsorSelect').addClass('d-block');
            });
        });

        $('#selectSponsor').change(function(){
            console.log('hallo');
            $('#selectSponsor option:selected').each(function(){
                $('.sponsoring').removeClass('d-none');
                $('.sponsoring').addClass('d-block');
            });
        });

    </script>
    <script>
        $(document).ready(function () {


            $("input").change(function() {
                checkChanged();
            });

            function checkChanged() {

                if (!$('#maxBetrag').val() && !$('#rundenBetrag').val() && !$('#festBetrag').val()) {
                    $("#btn-save").hide();
                } else {
                    $("#btn-save").show();
                }
            }
        });

    </script>

@endpush