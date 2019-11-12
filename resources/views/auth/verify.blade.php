@extends('layouts.layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    {{ __('E-mail-Adresse bestätigen') }}
                </div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('Ein Link zum bestätigen Ihrer E-mail-Adresse wurde versendet') }}
                        </div>
                    @endif

                    {{ __('Die E-Mail-Adresse muss bestätigt werden, bevor fortgefahren werden kann.') }}
                    {{ __('Sollten Sie keine E-Mail erhalten haben') }}, <a href="{{ route('verification.resend') }}">{{ __('klicken Sie hier um ein neue E-Mail anzufordern') }}</a>.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
