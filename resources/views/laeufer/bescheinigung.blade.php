<html>
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type">
    <style type="text/css">
        ol {
            margin: 0;
            padding: 0
        }



        .rechts {

            text-align: right
        }

        .fett {
            color: #000000;
            font-weight: 700;
            text-decoration: none;
            vertical-align: baseline;
            font-style: normal
        }


        @font-face {
            font-family: 'MetaPro';
            src: url({{asset('css/MetaPro-Normal.ttf')}}) format('truetype');
        }

        body {
            font-family: "MetaPro", serif;

            margin: 0;
            margin-right: 15px;

            height: 295.5mm;
            width: 200mm;

            max-width: 180mm;
            padding: 52pt 56.7pt 56.7pt 16.7pt;


            font-size: 10pt;
            font-weight: 400;

        }

        .new-page {
            page-break-before: always;
        }



        #bg {
            height: 285.5mm;
            width: 209mm;
            z-index: 5;
            position: absolute;
            left: -00px;
            top: -70px;
        }

        #anschrift{
            margin-top: 60pt;
        }



    </style>
</head>
<body>
    <img src="{{storage_path('app/briefbogen.jpg')}}" id="bg" style="margin-left: -30px;">


<div style="z-index: 100">
    <p id="anschrift">
        {{$laeufer->vorname}} {{$laeufer->nachname}}
    </p>
    <br>
    <p class="rechts">
        Radebeul, {{ date('d.m.Y') }}
    </p>
    <br><br><br>
    <p class="fett">
        Bestätigung der Teilnahme beim Spendenlauf des Evangelischen Schulverein Radebeul e.V. am {{config('config.spendenlauf.date')->format('d.m.Y')}}
    </p>
    <br><br>
    <p class="">
        Wir bestätigen, dass <b>{{$laeufer->vorname}} {{$laeufer->nachname}} (geb. {{optional($laeufer->geburtstag)->format('d.m.Y')}})</b> am Radebeuler Spendenlauf  am  {{config('config.spendenlauf.date')->format('d.m.Y')}}  im Lößnitzstadion teilgenommen hat.
    </p>
    <p class="">
        In der Laufzeit von 2 Stunden absolvierte {{$laeufer->vorname}} {{$laeufer->nachname}} {{$laeufer->runden}} Runden zu je 400m was einer Laufstrecke von {{ ($laeufer->runden * 400)/1000  }} km entspricht.
    </p>



    <p class="">
        Mit freundlichen Grüßen
    </p>
    <p>
        &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;

    </p>

    <p class="">

        Cornelia Ludwig &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;&nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;

    </p>
    <p class="">
        Projektverantwortliche        &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

    </p>


</div>

</body>
</html>
