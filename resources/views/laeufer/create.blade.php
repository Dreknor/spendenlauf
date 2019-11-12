@extends('layouts.app')

@section('content')
    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        {{__('neuen Läufer anmelden')}}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{url('laeufer')}}" method="post" class="form-horizontal">
                        @csrf
                        <div class="form-group row">
                            <label for="vorname" class="col-md-2 col-sm-5 col-form-label text-md-right">{{ __('Vorname') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <input id="vorname" type="text" class="form-control @error('vorname') is-invalid @enderror" name="vorname" value="{{ old('vorname') }}" required autocomplete="vorname" autofocus>

                                @error('vorname')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <label for="nachname" class="col-md-2 col-sm-5 col-form-label text-md-right">{{ __('Nachname') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <input id="nachname" type="text" class="form-control @error('nachname') is-invalid @enderror" name="nachname" value="{{ old('nachname') }}" required autocomplete="nachname" autofocus>

                                @error('nachname')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-md-2 col-sm-5 col-form-label text-md-right">{{ __('E-Mail') }}</label>

                            <div class="col-md-9 col-sm-7">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}"  autocomplete="email">

                                @error('email')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                            <div class="form-group row">
                            <label for="geburtsdatum" class="col-md-2 col-sm-5 col-form-label text-md-right">{{ __('Geburtsdatum') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <input id="geburtsdatum" type="date" class="form-control @error('geburtsdatum') is-invalid @enderror" name="geburtsdatum" value="{{ old('geburtsdatum') }}" required autocomplete="geburtsdatum" autofocus>

                                @error('geburtsdatum')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <label for="geschlecht" class="col-md-2 col-sm-5 col-form-label text-md-right">{{ __('Geschlecht') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <select name="geschlecht" class="custom-select @error('geschlecht') is-invalid @enderror">
                                    <option value="1">{{ __('männlich') }}</option>
                                    <option value="0">{{ __('weiblich') }}</option>
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
                                <input type="checkbox" name="datenschutz" required>
                            </div>

                            <div class="col-md-10 col-sm-10">
                                <label for="geschlecht" class="">{{ __('Ich bestätige, dass der Läufer (bzw. der Sorgeberechtigte des Läufers) mit den Teilnahmebedingungen einverstanden ist.') }}</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-2 col-sm-2  text-md-right">
                                <input type="checkbox" name="bilder" required>
                            </div>

                            <div class="col-md-10 col-sm-10">
                                <label for="bilder" class="">{{ __('Ich bestätige, dass der Läufer (bzw. der Sorgeberechtigte des Läufers) das Fotografieren und die Verwendung der Fotos gemäß Fotofreigabe genehmigt.') }}</label>
                            </div>
                        </div>

                        <div class=" ">
                            <button type="submit" class="btn btn-success btn-block">
                                {{__('Läufer anmelden')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
@endsection