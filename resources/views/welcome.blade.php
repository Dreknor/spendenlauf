@extends('layouts.layout')
@section('content')
                <div class="container-fluid  h-75">
                    <div class="row h-50 justify-content-center align-items-center">
                                    <h1 class="text-white">
                                        Radebeuler - Spendenlauf
                                    </h1>
                    </div>
                    <div class="row h-50  justify-content-center align-items-center">
                        <div class="col-12">
                            <div class="row">


                            @auth
                                <div class="col-md-12 col-sm-12">
                                    <a href="{{url('/home')}}" class="btn btn-lg btn-success btn-block">zur Läuferverwaltung</a>
                                </div>

                            @else
                                <div class="col-md-6 col-sm-12">
                                    <a href="{{url('/register')}}" class="btn btn-lg btn-primary btn-block">Registrieren</a>
                                </div>
                                <div class="col-md-6 col-sm-12">
                                    <a href="{{url('/login')}}" class="btn btn-lg btn-success btn-block">Login</a>
                                </div>
                            @endauth
                            </div>
                        </div>
                        <div class="col-12">
                            <a href="{{url('projects')}}" class="btn btn-block btn-lg btn-secondary">
                                {{__('unsere Projekte')}}
                            </a>
                        </div>

                    </div>


                </div>
@endsection