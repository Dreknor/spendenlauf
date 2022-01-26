<!DOCTYPE html>
<html>
<head>
    <title>{{config('app.name')}} - Neuer Läufer im Team</title>
</head>
<body>

<p>Liebe/r {{$name}},</p>
<p>
    Der Läufer {{$Laeufer}} wurde deinem Team {{$Team}} hinzugefügt.
</p>
<p>
    Eine Übersicht über die Läufer deines Teams findest du unter <a href="{{config('app.url')}}">{{config('app.url')}}</a>
</p>
</body>
</html>
