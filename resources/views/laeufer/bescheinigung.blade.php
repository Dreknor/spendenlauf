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
    <img src="{{storage_path('app/urkunde_blank-scaled.png')}}" id="bg" style="margin-left: -40px;">


<div style="z-index: 100">
    <div id="" style="position: absolute; top: 30%; left: 50%;">
        <div style="font-size: 20pt; font-weight: 700; ">
            {{$laeufer->vorname}} {{$laeufer->nachname}}
        </div>
        <div style="font-size: 12pt; font-weight: 400; ">
            @if($laeufer->geburtsdatum != null)
                geb. {{$laeufer->geburtsdatum?->format('d.m.Y')}}
            @endif
        </div>
    </div>
    <div id="" style="position: absolute; top: 50%; left: 30%;">

        <div style="font-size: 16pt; font-weight: 400; ">
                In der Laufzeit von 2 Stunden absolvierte Irene Schumann 26 Runden zu je 400m was einer Laufstrecke von 10.4 km
                entspricht.
            </div>
    </div>




</div>

</body>
</html>
