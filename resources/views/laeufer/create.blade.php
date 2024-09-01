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
                            <label for="vorname" class="col-md-2 col-sm-5 col-form-label text-danger text-md-right">{{ __('Vorname') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <input id="vorname" type="text" class="form-control @error('vorname') is-invalid @enderror" name="vorname" value="{{ old('vorname') }}" required autocomplete="vorname" autofocus>

                                @error('vorname')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <label for="nachname" class="col-md-2 col-sm-5 col-form-label text-danger text-md-right">{{ __('Nachname') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <input id="nachname" type="text" class="form-control  @error('nachname') is-invalid @enderror" name="nachname" value="{{ old('nachname') }}" required autocomplete="nachname" autofocus>

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
                            <label for="geburtsdatum" class="col-md-2 col-sm-5 col-form-label text-md-right text-danger">{{ __('Geburtsdatum') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <input id="geburtsdatum" type="date" class="form-control @error('geburtsdatum') is-invalid @enderror" name="geburtsdatum" value="{{ old('geburtsdatum') }}" autocomplete="geburtsdatum" autofocus>

                                @error('geburtsdatum')
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <label for="geschlecht" class="col-md-2 col-sm-5 col-form-label text-md-right">{{ __('Geschlecht') }}</label>

                            <div class="col-md-4 col-sm-7">
                                <select name="geschlecht" class="custom-select @error('geschlecht') is-invalid @enderror">
                                    <option value=""></option>
                                    <option value="maennlich">{{ __('männlich') }}</option>
                                    <option value="weiblich">{{ __('weiblich') }}</option>
                                    <option value="divers">{{ __('divers') }}</option>
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
                                <label for="datenschutz">
                                    Ich bestätige, dass der Läufer/ die Läuferin (bzw. der Sorgeberechtigte des Läufers/der Läuferin) mit den <a href="{{config('config.datenschutz.link')}}" target="_blank">Teilnahmebedingungen und dem Datenschutz </a>einverstanden ist.
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-2 col-sm-2  text-md-right">
                                <input type="checkbox" name="bilder" required checked>
                            </div>

                            <div class="col-md-10 col-sm-10">
                                <label for="bilder" class="">Ich bestätige, dass der Läufer/der Läuferin (bzw. der Sorgeberechtigte des Läufers/der Läuferin) das Fotografieren und die Verwendung der Fotos gemäß Datenschutz genehmigt.</label>
                            </div>
                        </div>
                        <hr>
                        <h6>
                           Team
                        </h6>
                        <p>
                            Wenn Sie ein neues Team anlegen oder auswählen, wird der Läufer automatisch diesem Team zugeordnet. Teams sind jedoch nicht verpflichtend.
                        </p>

                        <div class="form-group row">
                            <div class="col-md-6 col-sm-12">
                                <label for="team_name" class="">{{ __('Neues Team anlegen') }}</label>
                                <input type="text" name="team_name" class="form-control" value="{{old('team_name')}}">
                            </div>
                            <div class="col-md-6 col-sm-12 ">
                                <label for="team_id" class="">{{ __('bestehendem Team beitreten') }}</label>
                                <select name="team_id" class="custom-select">
                                    <option value=""></option>
                                    @foreach($teams as $team)
                                        <option value="{{$team->id}}">{{$team->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class=" ">
                            <button type="submit" class="btn btn-success btn-block">
                                {{__('Anmelden')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
@endsection
