@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                {{__('Team bearbeiten')}}
            </h5>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <p class="card-title">
                                Team-Daten
                            </p>
                        </div>
                        <div class="card-body">
                            <form action="{{url("/teams/$team->id")}}" method="post" class="form form-horizontal">
                                @csrf
                                @method('put')
                                <div class="row">
                                    <div class="col">
                                        <p class="alert alert-info">
                                            <b>Hinweis: </b> Teams, die öffentlich sind, können von anderen Läufern gesehen und diesen beigetreten werden. Bei nicht öffentlichen Teams muss der Ersteller des Teams auch alle Läufer anmelden um diese in das Team aufnehmen zu können.
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label>{{__('Name des Teams')}}</label>
                                            <input type="text" class="form-control border-input"  name="name" value="{{old('name', $team->name)}}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label>{{__('Name des Teams')}}</label>
                                            <select type="text" class="custom-select"  name="open" required>
                                                <option value="1" @if($team->open ) selected @endif>
                                                    {{__('öffentlich')}}
                                                </option>
                                                <option value="0" @if(!$team->open ) selected @endif>
                                                    {{__('nicht öffentlich')}}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                            {{__('Änderungen speichern')}}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <p class="card-title">
                                {{__('Läufer')}}
                            </p>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @foreach($team->laeufer as $laeufer)
                                    <li class="list-group-item">
                                        @if(auth()->user()->can('edit laeufer') or $laeufer->besitzer->id == auth()->user()->id)
                                            <a href="{{url("laeufer/$laeufer->id")}}" class="card-link">
                                                {{$laeufer->name}}
                                            </a>
                                        @else
                                            {{$laeufer->name}}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <p class="card-title">
                                Spenden
                            </p>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">
                                Die Beträge sind in der Reihenfolge Festbetrag, Rundenbetrag und maximaler Betrag angegeben.
                            </p>
                            <ul class="list-group">
                                @foreach($team->sponsorings as $sponsoring)
                                    @include('elements.SponsoringItem')
                                @endforeach
                            </ul>

                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

@endsection

@push('css')


@endpush

@push('js')
    <script>
        $(document).ready(function () {

            $("input").keyup(function() {
                checkChanged();
            });
            $("select").change(function() {
                checkChanged();
            });

            $(":checkbox").change(function() {
                checkChanged();
            });

            function checkChanged() {

                if (!$('input').val()) {
                    $("#submitBtn").hide();
                } else {
                    $("#submitBtn").show();
                }
            }
        });

    </script>
@endpush
