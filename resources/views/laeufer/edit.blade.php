@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    {{__('Läufer bearbeiten')}}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <b>
                                    {{__('Läuferdaten')}}
                                </b>
                            </div>
                            <div class="card-body">
                                <form action="{{url('laeufer/'.$Laeufer->id)}}" method="post" class="form-horizontal">
                                    @csrf
                                    @method('put')
                                    <div class="form-group row">
                                        <label for="vorname" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Vorname') }}</label>

                                        <div class="col-md-8 col-sm-7">
                                            <input id="vorname" type="text" class="form-control @error('vorname') is-invalid @enderror" name="vorname" value="{{$Laeufer->vorname}}" required autocomplete="vorname" autofocus>

                                            @error('vorname')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="nachname" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Nachname') }}</label>

                                        <div class="col-md-8 col-sm-7">
                                            <input id="nachname" type="text" class="form-control @error('nachname') is-invalid @enderror" name="nachname" value="{{$Laeufer->nachname}}" required autocomplete="nachname" autofocus>

                                            @error('nachname')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="email" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('E-Mail') }}</label>

                                        <div class="col-md-8 col-sm-7">
                                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{$Laeufer->email}}"  autocomplete="email">
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="geburtsdatum" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Geburtsdatum') }}</label>

                                        <div class="col-md-8 col-sm-7">
                                            <input id="geburtsdatum" type="date" class="form-control @error('geburtsdatum') is-invalid @enderror" name="geburtsdatum" value="{{$Laeufer->geburtsdatum->format('Y-m-d')}}" required autocomplete="geburtsdatum" autofocus>
                                            @error('geburtsdatum')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="geschlecht" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Geschlecht') }}</label>

                                        <div class="col-md-8 col-sm-7">
                                            <select name="geschlecht" class="custom-select @error('geschlecht') is-invalid @enderror">
                                                <option value="1" @if($Laeufer->geschlecht == 1) selected @endif>{{ __('männlich') }}</option>
                                                <option value="0" @if($Laeufer->geschlecht != 1) selected @endif>{{ __('weiblich') }}</option>
                                            </select>
                                            @error('geschlecht')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-2 col-sm-2  text-md-right">
                                            <input type="checkbox" name="datenschutz" required checked>
                                        </div>

                                        <div class="col-md-10 col-sm-10">
                                            <label for="geschlecht" class="">{{ __('Ich bestätige, dass der Läufer (bzw. der Sorgeberechtigte des Läufers) mit den Teilnahmebedingungen einverstanden ist.') }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-2 col-sm-2  text-md-right">
                                            <input type="checkbox" name="bilder" required checked>
                                        </div>

                                        <div class="col-md-10 col-sm-10">
                                            <label for="bilder" class="">{{ __('Ich bestätige, dass der Läufer (bzw. der Sorgeberechtigte des Läufers) das Fotografieren und die Verwendung der Fotos gemäß Fotofreigabe genehmigt.') }}</label>
                                        </div>
                                    </div>

                                    <div class="">
                                        <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">
                                            {{__('Änderungen speichern')}}
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <b>
                                    {{__('Spendenlauf 2019')}}
                                </b>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        {{__('Startnummer:')}} {{$Laeufer->startnummer}}
                                    </div>
                                    <div class="col-md-6 col-sm-12">
                                       {{__('Runden:')}} @if(is_null($Laeufer->runden)) 0 @else {{$Laeufer->runden}} @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <b>
                                    {{__('Team')}}
                                </b>
                            </div>
                            <div class="card-body">
                                @if($Laeufer->team_id != null)
                                    <div class="row mx-auto">
                                        <div class="col">
                                            <a href="{{url("teams/".$Laeufer->team->id)}}" class="btn btn-outline-info btn-block @if(!auth()->user()->can('edit teams') and $Laeufer->team->besitzer->id != auth()->user()->id) disabled @endif ">
                                                {{$Laeufer->team->name}}
                                            </a>
                                        </div>
                                        <div class="col">
                                            <form action="{{url("laeufer/$Laeufer->id/removeTeam")}}" method="post">
                                                @csrf
                                                @method('put')
                                                <button type="submit" class="btn btn-danger">
                                                    {{__('Team verlassen')}}
                                                </button>
                                            </form>
                                        </div>
                                    </div>


                                @else
                                    <p>
                                        {{__('Der Läufer ist in keinem Team')}}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="card">
                                <div class="card-header">
                                    <p class="card-title">
                                        <b>
                                            {{__('Sponsorings')}}
                                        </b>
                                    </p>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted">
                                        {{__('Die Beträge sind in der Reihenfolge Festbetrag, Rundenbetrag und maximaler Betrag angegeben.')}}
                                    </p>
                                    <ul class="list-group">
                                        @foreach($Laeufer->sponsorings as $sponsoring)
                                           @include('elements.SponsoringItem')
                                        @endforeach
                                    </ul>

                                </div>
                            </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

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
                    $("#btn-save").hide();
                } else {
                    $("#btn-save").show();
                }
            }
        });

    </script>

@endpush