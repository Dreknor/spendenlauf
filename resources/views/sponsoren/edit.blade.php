@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <h5 class="card-title">
                                    {{__('Spender bearbeiten')}}
                                </h5>
                            </div>
                            <div class="col-sm-12 col-md-4">
                                <div class="pull-right">
                                    <a href="{{url("sponsor/sendMail/$sponsor->id")}}" class="btn btn-warning">
                                        <i class="far fa-paper-plane"></i>
                                        {{__('Mailing versenden')}}
                                    </a>
                                </div>

                            </div>

                        </div>

                    </div>
                    <div class="card-body">
                        <form action="{{url("sponsoren/$sponsor->id")}}" method="post" class="form-horizontal">
                            @csrf
                            @method("put")
                            <div class="form-group row">
                                <label for="anrede" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Anrede') }}</label>

                                <div class="col-md-8 col-sm-7">
                                    <select name="anrede" class="custom-select @error('anrede') is-invalid @enderror" id="anredeSelect">
                                        <option value="Herr" @if($sponsor->anrede == "Herr") selected @endif>{{ __('Herr') }}</option>
                                        <option value="Frau" @if($sponsor->anrede == "Frau") selected @endif>{{ __('Frau') }}</option>
                                        <option value="Firma" @if($sponsor->anrede == "Firma") selected @endif>{{ __('Firma') }}</option></select>
                                    @error('anrede')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="vorname" class="col-md-42 col-sm-5 col-form-label text-md-right">{{ __('Vorname') }}</label>

                                <div class="col-md-8 col-sm-7">
                                    <input id="vorname" type="text" class="form-control @error('vorname') is-invalid @enderror" name="vorname" value="{{ old('vorname', $sponsor->vorname) }}" required autocomplete="vorname" autofocus>

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
                                    <input id="nachname" type="text" class="form-control @error('nachname') is-invalid @enderror" name="nachname" value="{{ old('nachname', $sponsor->nachname) }}" required autocomplete="nachname" >

                                    @error('nachname')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                                <div class="form-group row">
                                    <label for="firmenname" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Firmenname') }}</label>
                                    <div class="col-md-8 col-sm-7">
                                        <input id="firmenname" type="text" class="form-control @error('firmenname') is-invalid @enderror" name="firmenname" value="{{ old('firmenname', $sponsor->firmenname) }}"  autocomplete="firmenname" >
                                        @error('firmenname')
                                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                        @enderror
                                    </div>
                                </div>
                            <div class="form-group row">
                                <label for="email" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('E-Mail') }}</label>

                                <div class="col-md-8 col-sm-7">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $sponsor->email) }}"  autocomplete="email">

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="telefon" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Telefon') }}</label>

                                <div class="col-md-8 col-sm-7">
                                    <input id="telefon" type="text" class="form-control @error('telefon') is-invalid @enderror" name="telefon" value="{{ old('telefon', $sponsor->telefon) }}"  autocomplete="telefon">

                                    @error('telefon')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="strasse" class="col-md-4 col-sm-5 col-form-label text-md-right">{{ __('Anschrift') }}</label>

                                <div class="col-md-8 col-sm-7">
                                    <input id="strasse" type="text" class="form-control @error('strasse') is-invalid @enderror" name="strasse" value="{{ old('strasse', $sponsor->strasse) }}" required autocomplete="strasse" >

                                    @error('strasse')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="plz" class="col-md-3 col-sm-5 col-form-label text-md-right">{{ __('Plz') }}</label>

                                <div class="col-md-2 col-sm-7">
                                    <input id="plz" type="text" class="form-control @error('plz') is-invalid @enderror" name="plz" value="{{ old('plz', $sponsor->plz) }}" required autocomplete="plz" >

                                    @error('plz')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <label for="ort" class="col-md-1 col-sm-5 col-form-label text-md-right">{{ __('Ort') }}</label>

                                <div class="col-md-5 col-sm-7">
                                    <input id="ort" type="text" class="form-control @error('ort') is-invalid @enderror" name="ort" value="{{ old('ort', $sponsor->ort) }}" required autocomplete="ort" >

                                    @error('ort')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-2 col-sm-2  text-md-right">
                                    <input type="checkbox" name="datenschutz" required>
                                </div>

                                <div class="col-md-10 col-sm-10">
                                    <label for="datennutzung" class="">{{ __('Ich bestätige, dass der Spender mit der Verarbeitung seiner Daten einverstanden ist.') }}</label>
                                </div>
                            </div>

                            <div class=" ">
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
                        <p class="card-title">
                            <b>
                                {{__('Spende: ')}}
                            </b>
                            {{number_format($sponsor->Spendensumme,2)}} €
                        </p>
                    </div>

                </div>
                <div class="card">
                    <div class="card-header">
                        <p class="card-title">
                            <b>Spenden
                            </b>
                        </p>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">
                            Die Beträge sind in der Reihenfolge Festbetrag, Rundenbetrag und maximaler Betrag angegeben.
                        </p>
                        <ul class="list-group">
                            @foreach($sponsor->sponsorings as $sponsoring)
                                @include('elements.SponsoringItem')
                            @endforeach
                        </ul>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('js')
    <script>
        $('#anredeSelect').change(function(){
            $('#anredeSelect option:selected').each(function(){
                if($(this).text() == "Firma"){
                    $('#firmenRow').show();
                } else {
                    $('#firmenRow').hide();

                }
            });
        });

    </script>

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
