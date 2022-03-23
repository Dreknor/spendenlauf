@component('mail::message')


{{optional($sponsor)->firmenname}}<br>
{{$sponsor->vorname}} {{$sponsor->nachname}}<br>
{{$sponsor->strasse}}<br>
{{$sponsor->plz}} {{$sponsor->ort}}

# Spendenlauf am {{config('config.spendenlauf.date')->format('d.m.Y')}}
<br>
vielen Dank, dass Sie durch Ihre Sach- oder Geldspende unsere Projekte in Radebeul unterstützt haben.

Der Spendenlauf am {{config('config.spendenlauf.date')->format('d.m.Y')}} war ein großer Erfolg. {{$countLaeufer}} Läufer haben {{number_format($spendensumme,2)}} € erlaufen. Wir sind überwältigt, dass sich so viele Menschen an
dieser Aktion beteiligt haben. An dieser Stelle bedanken wir uns ganz herzlich für Ihre Unterstützung.
<br>
Bitte beachten Sie, dass 30% der Spendensummer für Hilfsprojekt im Rahmen der Ukrainekrise verwendet werden.

@component('mail::table')
    @php($Spendensumme=0)
    | Spende für | Runden        | Spende je Runde       |        Festbetrag        |    max. Betrag       |  Summe       |
    | -------------  |:-------------:| ---------------------:|-------------------------:|---------------------:|-------------:|
    @foreach($sponsor->sponsorings as $sponsoring)
        @php($spende=number_format($sponsoring->spende,2))
    | {{$sponsoring->sponsorable->name}} | {{$sponsoring->sponsorable->runden}} | {{number_format($sponsoring->rundenBetrag,2)}} € | {{number_format($sponsoring->festBetrag,2)}} € | {{number_format($sponsoring->maxBetrag,2)}} € | {{number_format($sponsoring->spende,2)}} € |
    @php($Spendensumme+=$spende)
    @endforeach
    |   |  |  |   |  | {{number_format($Spendensumme,2)}} €|


@endcomponent

Bitte überweisen Sie, falls noch nicht geschehen, den Betrag auf das Konto:

#DasKonto fehlt noch


Sollten Sie eine Spendenbescheinigung benötigen, so teilen Sie uns dies bitte mit.
Fotos vom Spendenlauf und aktuelle Informationen des Schulzentrums finden Sie in den
nächsten Tagen unter <a href="https://www.radebeuler-spendenlauf.de">www.radebeuler-spendenlauf.de</a>.
Wir freuen uns, wenn Sie unserem Projekt verbunden bleiben


Radebeul, {{\Carbon\Carbon::now()->format('d.m.Y')}}
@endcomponent
