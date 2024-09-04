@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-info">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <h5 class="card-title text-white">
                            {{__('Team-Übersicht')}}
                        </h5>
                    </div>
                    <div class="col-md-4 col-sm-12 content-center">
                        <a href="{{url('teams/create')}}" class="btn btn-success">
                            {{__('Team erstellen')}}
                        </a>
                    </div>
                </div>

            </div>
            <div class="card-body">
                @if(count($teams)<1)
                    <p>
                        {{__('Sie haben bisher keine Teams erstellt.')}}
                    </p>
                @else
                    <div class="table-responsive-md  ">
                        <table class="table table-striped">
                            <thead>
                            <th>{{__('Teamname')}}</th>
                            <th>{{__('Anzahl Läufer')}}</th>
                            <th>{{__('Spenden')}}</th>
                            <th>{{__('Runden')}}</th>
                            <th>{{__('Öffentlich?')}}</th>
                            @can('edit teams')
                                <th>{{__('Ersteller')}}</th>
                            @endcan
                            <th></th>
                            </thead>
                            <tbody>
                            @foreach($teams as $team)
                                <tr>
                                    <td>
                                        {{$team->name}}
                                    </td>
                                    <td>
                                        {{$team->laeufer->count()}}
                                    </td>
                                    <td>
                                        {{$team->sponsorings->count()}}
                                    </td>
                                    <td>
                                        {{$team->laeufer->sum('runden')}}
                                    </td>
                                    <td>
                                        @if($team->open)
                                            {{__('öffentlich')}}
                                        @else
                                            {{__('nicht öffentlich')}}
                                        @endif
                                    </td>
                                    @can('edit teams')
                                        <td>{{$team->besitzer->name}}</td>
                                    @endcan
                                    <td>
                                        <a href="{{url('teams/'.$team->id.'/edit')}}" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit d-block d-md-none "></i>
                                            <div class="d-none d-md-block">
                                                {{__('anzeigen')}}
                                            </div>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready( function () {
            $('table').DataTable();
        } );
    </script>


@endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />
@endsection
