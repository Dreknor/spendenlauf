@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-info">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <h5 class="card-title text-white">
                            {{__('Läufer-Übersicht')}}
                        </h5>
                    </div>
                    <div class="col-md-4 col-sm-12 content-center">
                        <a href="{{url('laeufer/create')}}" class="btn btn-success">
                            <i class="fas fa-user-plus"></i>
                            {{__('Läufer anmelden')}}
                        </a>
                    </div>
                </div>

            </div>
            <div class="card-body">
                @if(count($laeufer)<1)
                    <p>
                        {{__('Sie haben bisher keine Läufer angemeldet.')}}
                    </p>
                @else
                    <div class="table-responsive-md  ">
                        <table class="table table-striped">
                        <thead>
                            <th>{{__('Startnummer')}}</th>
                            <th>{{__('Familienname')}}</th>
                            <th>{{__('Vorname')}}</th>
                            <th>{{__('Alter')}}</th>
                            <th>{{__('Team')}}</th>
                            @can('edit laeufer')
                                <th>{{__('Ersteller')}}</th>
                            @endcan
                            <th></th>
                        </thead>
                        <tbody>
                            @foreach($laeufer as $Laeufer)
                                <tr>
                                    <td>
                                        {{$Laeufer->startnummer}}
                                    </td>
                                    <td>
                                        {{$Laeufer->nachname}}
                                    </td>
                                    <td>
                                        {{$Laeufer->vorname}}
                                    </td>
                                    <td>
                                        {{$Laeufer->age}}
                                    </td>
                                    <td>
                                        @if(isset($Laeufer->team))
                                            {{$Laeufer->team->name}}
                                        @else
                                            <a href="{{url('laeufer/'.$Laeufer->id.'/addTeam')}}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-user-friends d-block d-md-none"></i>
                                                <div class="d-none d-md-block">
                                                    {{__('Team hinzufügen')}}
                                                </div>
                                            </a>
                                        @endif
                                    </td>
                                    @can('edit laeufer')
                                        <td>{{$Laeufer->besitzer->name}}</td>
                                    @endcan
                                    <td>
                                        <a href="{{url('laeufer/'.$Laeufer->id.'/edit')}}" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-user-edit d-block d-md-none "></i>
                                            <div class="d-none d-md-block">
                                                {{__('anzeigen')}}
                                            </div>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6">
                                        {{$laeufer->links()}}
                                    </td>
                                </tr>
                            </tfoot>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
