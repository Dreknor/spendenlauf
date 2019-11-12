@extends('layouts.app')

@section('content')
    <form action="{{url('/users/').'/'.$user->id}}" method="post" class="form form-horizontal">
        @csrf
        @method('PUT')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title">
                    {{$user->name}}
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Benutzer-Einstellungen
                                </h5>
                            </div>

                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                    <div class="row">
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Vorname</label>
                                                <input type="text" class="form-control border-input" placeholder="Vorname" name="vorname" value="{{$user->vorname}}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label>Nachname</label>
                                                <input type="text" class="form-control border-input" placeholder="Nachname" name="nachname" value="{{$user->nachname}}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>E-Mail</label>
                                                <input type="text" class="form-control border-input" placeholder="E-Mail" name="email" value="{{$user->email}}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">speichern</button>
                                        </div>
                                    </div>

                            </div>
                        </div>

                    </div>


                    <div class="col-md-3 col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    Rechte
                                </h5>
                            </div>
                            <div class="card-body">
                                @can('edit permission')
                                    @foreach($permissions as $permission)
                                        <div>
                                            <input type="checkbox" id="{{$permission->name}}" name="permissions[]" value="{{$permission->name}}" @if($user->can($permission->name)) checked @endif>
                                            <label for="{{$permission->name}}">{{$permission->name}}</label>
                                        </div>
                                    @endforeach
                                @else
                                    <p>Kein Recht zur Rechtevergabe</p>
                                @endcan
                            </div>
                            <div class="card-footer">

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </form>

@endsection

@push('js')

    <script>
        $(document).ready(function () {


            $("input").keyup(function() {
                checkChanged();
            });
            $("select").change(function() {
                checkChanged();
            });

            $(":checkbox").change(function() {
                checkChanged();
            });

            function checkChanged() {

                if (!$('input').val()) {
                    $("#btn-save").hide();
                } else {
                    $("#btn-save").show();
                }
            }
        });

    </script>

@endpush