<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="76x76" href="./assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="./assets/img/favicon.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

    <title>{{ config('app.name') }}</title>

    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no' name='viewport' />
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" />
    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/own.css')}}" rel="stylesheet" />
    <style type="text/css"> html, body{ height: 100%; } .full-page{ height: 100vh; width: 100vw; } </style>
</head>

<body class="index-page sidebar-collapse bodyBg">
<div class="container-fluid">
    <a href="{{config('config.logo.logo')}}" class="simple-text">
        <div class="logo-image-small">
            <img src="{{asset(config('config.logo.logo'))}}" class="bg-light " height="150px;" alt="Logo">
        </div>
    </a>
</div>
<div class="container-fluid  h-75">
    @yield('content')
</div>



</body>

</html>
