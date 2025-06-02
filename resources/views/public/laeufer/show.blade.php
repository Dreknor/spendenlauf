@extends('layouts.layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <div class="row">
                        <div class="col-md-12">
                            <h2>
                                {{config('app.name')}}
                            </h2>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <i class="fa fa-calendar"></i>     {{\Carbon\Carbon::parse(config('config.spendenlauf.date'))?->format('d.m.Y')}}
                        </div>
                    </div>
                </div>
                <div class="card-header">
                    <h4>Unsere unterstützten Projekte</h4>
                    @foreach($projekte as $projekt)
                        <details class="list-group-item">
                            <summary>
                                {{$projekt->name}}
                            </summary>
                            {!! $projekt->description !!}
                            @if($projekt->getFirstMedia('images') != null)
                                <img src="{{url('/image/'.$projekt->getFirstMedia('images')->id)}}" class="img-thumbnail rounded" style="height: 200px;">
                            @endif
                        </details>
                    @endforeach
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h3>
                                Läufer: {{ $laeufer->vorname }} {{ $laeufer->nachname }}
                            </h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            Alter: {{$laeufer?->geburtsdatum->diffInYears()}}
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6 col-sm-12 mb-2">
                            <strong>Gesammelt Festbetrag:</strong> EUR 0.00
                        </div>
                        <div class="col-md-6 col-sm-12 mb-2">
                            <strong>Gesammelt pro Runde:</strong> EUR 0.00
                        </div>
                        <div class="col-md-6 col-sm-12 mb-2">
                            <strong>Gesammelt total:</strong> EUR 0.00
                        </div>
                        <div class="col-md-6 col-sm-12 mb-2">
                            <strong>Runden:</strong> {{$laeufer->runden}}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    @if(\Carbon\Carbon::parse(config('config.spendenlauf.date'))->gte(now()))
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">{{ $laeufer->vorname }} {{ $laeufer->nachname }} unterstützen</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('public.sponsor.store', $laeufer->uuid) }}">
                            @csrf
                            <div class=" row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            Spender-Daten
                                        </div>
                                        <div class="card-body">
                                            @if (session('success'))
                                                <div class="alert alert-success">
                                                    {{ session('success') }}
                                                </div>
                                            @endif



                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Anrede *</label>
                                                <div class="col-md-6">
                                                    <select name="anrede" class="form-control @error('anrede') is-invalid @enderror" required>
                                                        <option value="">Bitte wählen</option>
                                                        <option value="Herr" {{ old('anrede') == 'Herr' ? 'selected' : '' }}>Herr</option>
                                                        <option value="Frau" {{ old('anrede') == 'Frau' ? 'selected' : '' }}>Frau</option>
                                                        <option value="Herr" {{ old('anrede') == 'Firma' ? 'selected' : '' }}>Firma</option>
                                                    </select>
                                                    @error('anrede')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Vorname *</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('vorname') is-invalid @enderror"
                                                           name="vorname" value="{{ old('vorname') }}" required>
                                                    @error('vorname')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Nachname *</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('nachname') is-invalid @enderror"
                                                           name="nachname" value="{{ old('nachname') }}" required>
                                                    @error('nachname')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Firmenname</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('firmenname') is-invalid @enderror"
                                                           name="firmenname" value="{{ old('firmenname') }}">
                                                    @error('firmenname')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">E-Mail *</label>
                                                <div class="col-md-6">
                                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                           name="email" value="{{ old('email') }}" required>
                                                    @error('email')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Straße *</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('strasse') is-invalid @enderror"
                                                           name="strasse" value="{{ old('strasse') }}" required>
                                                    @error('strasse')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">PLZ *</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('plz') is-invalid @enderror"
                                                           name="plz" value="{{ old('plz') }}" required>
                                                    @error('plz')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Ort *</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('ort') is-invalid @enderror"
                                                           name="ort" value="{{ old('ort') }}" required>
                                                    @error('ort')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Telefon</label>
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control @error('telefon') is-invalid @enderror"
                                                           name="telefon" value="{{ old('telefon') }}">
                                                    @error('telefon')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            Spende
                                        </div>
                                        <div class="card-body">
                                            <p class="card-description text-muted small">
                                                Es gibt verschiedene Möglichkeiten den Läufer zu unterstützen:
                                                Der Festbetrag ist eine festgelegt Spendenhöhe, die auf jeden Fall gespendet werden soll, unabhängig von der Anzahl der gelaufenen Runden. <br>
                                                Es kann auch ein Betrag je gelaufener Runde festgelegt werden. Dieser wird mit der Anzahl der Runden multiplziert. Um eine gewisse Planungssicherheit zu haben ist es dabei auch möglich dem maximalen Betrag einen Wert zu geben.
                                                <br>
                                                Kombinationen aus Festbetrag und Rundenbetrag sind ebenfalls möglich. Dabei werden der Festbetrag und der Rundenbetrag addiert. Auch hier gilt der maximale Betrag als Höchstgrenze.
                                            </p>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Festbetrag (€)</label>
                                                <div class="col-md-8">
                                                    <input type="number" step="0.50" class="form-control @error('festBetrag') is-invalid @enderror"
                                                           name="festBetrag" value="{{ old('festBetrag') }}">
                                                    @error('festBetrag')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">Spende je Runde (€)</label>
                                                <div class="col-md-8">
                                                    <input type="number" step="0.50" class="form-control @error('rundenBetrag') is-invalid @enderror"
                                                           name="rundenBetrag" value="{{ old('rundenBetrag') }}">
                                                    @error('rundenBetrag')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-4 col-form-label text-md-right">maximale Spende (€)</label>

                                                <div class="col-md-8">
                                                    <input type="number" step="0.50" class="form-control @error('maxBetrag') is-invalid @enderror"
                                                           name="maxBetrag" value="{{ old('maxBetrag') }}">
                                                    @error('maxBetrag')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="form-group row mb-0">
                                    <div class="col-md-6 offset-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            Spende bestätigen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
