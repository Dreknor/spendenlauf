@component('mail::message')


{{optional($sponsor)->firmenname}}<br>
{{optional($sponsor)->vorname}} {{optional($sponsor)->nachname}}<br>
{{$sponsor->strasse}}<br>
{{$sponsor->plz}} {{$sponsor->ort}}
<br>
<br>
# Spendenlauf am {{config('config.spendenlauf.date')->format('d.m.Y')}}
<br>
<br>

{{$sponsor->anrede_brief}}

vielen Dank, dass Sie durch Ihre Sach- oder Geldspende unsere Projekte in Radebeul unterstützt haben.

Der Spendenlauf am {{config('config.spendenlauf.date')->format('d.m.Y')}} war ein großer Erfolg. {{$countLaeufer}} Läufer haben {{number_format($spendensumme,2)}} € erlaufen. Wir sind überwältigt, dass sich so viele Menschen an
dieser Aktion beteiligt haben. An dieser Stelle bedanken wir uns ganz herzlich für Ihre Unterstützung.
<br>
Bitte beachten Sie, dass 30% der Spendensumme für Hilfsprojekte im Rahmen der Ukrainekrise verwendet werden.

@component('mail::table')
    @php($Spendensumme=0)
    | Spende für | Runden        | Spende <br>je Runde       |        Festbetrag        |    max. Betrag       |  Summe       |
    | -------------  |:-------------:| ---------------------:|-------------------------:|---------------------:|-------------:|
    @foreach($sponsor->sponsorings as $sponsoring)
    | {{$sponsoring->sponsorable->name}} | {{$sponsoring->sponsorable->runden}} | {{number_format($sponsoring->rundenBetrag,2)}} € | {{number_format($sponsoring->festBetrag,2)}} € | {{number_format($sponsoring->maxBetrag,2)}} € | {{number_format($sponsoring->spende,2)}} € | {{$sponsoring->spende}} |
    @php($Spendensumme+=$sponsoring->spende)
    @endforeach
    |   |  |  |   |  | {{number_format($Spendensumme,2)}} €|


@endcomponent


@if($sponsoring_projects > 0)
        Bitte überweisen Sie Ihren Spendenbetrag in Höhe von {{number_format($Spendensumme,2)}} € (falls noch nicht geschehen) auf das Konto des Kirchspiel in der Lößnitz.

        Kirchspiel in der Lößnitz
        DE06 3506 0190 1667 2090 28,
        KD Bank für Kirche und Diakonie, Kassenverwaltung Dresden
        Verwendungszweck (wichtig): 1082 Spendenlauf Lößnitz
@else
        Bitte überweisen Sie Ihren Spendenbetrag in Höhe von {{number_format($Spendensumme,2)}} € (falls noch nicht geschehen) auf das Konto des Schulvereins und wir leiten die Gelder dann an die Projekte weiter.

        Evangelischer Schulverein Radebeul e.V.
        Sparkasse Meißen
        IBAN: DE77 8505 5000 3000 0401 10
        BIC: SOLADES1MEI
        Verwendungszweck: Spendenlauf2023
@endif


@if($Spendensumme > 200)
Sollten Sie eine Spendenbescheinigung benötigen, so teilen Sie uns dies bitte mit.
@else
Bei Spenden bis 200€ akzeptieren die Finanzämter den Überweisungsbeleg als Spendennachweis für die Steuererklärung.
@endif

Fotos vom Spendenlauf und aktuelle Informationen finden Sie in den
nächsten Tagen unter <a href="https://www.radebeuler-spendenlauf.de">www.radebeuler-spendenlauf.de</a>.
Wir freuen uns, wenn Sie unserem Projekt verbunden bleiben


Radebeul, {{\Carbon\Carbon::now()->format('d.m.Y')}}
@endcomponent
