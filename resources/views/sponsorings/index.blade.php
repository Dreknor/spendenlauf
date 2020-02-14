@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-info">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <h5 class="card-title text-white">
                            {{__('Sponsoring-Übersicht')}}
                        </h5>
                    </div>
                    <div class="col-md-4 col-sm-12 content-center">
                        <a href="{{url('sponsorings/create')}}" class="btn btn-success">
                            {{__('Spende erstellen')}}
                        </a>
                    </div>
                </div>

            </div>
            <div class="card-body">
                @if(count($sponsorings)<1)
                    <p>
                        {{__('Sie haben bisher kein Spende erstellt.')}}
                    </p>
                @else
                    <div class="table-responsive-md">
                        <table class="table table-striped" id="sponsoringTable">
                            <thead>
                            <th>{{__('Spendername')}}</th>
                            <th>{{__('sponsert')}}</th>
                            @can('edit sponsoring')
                                <th>{{__('Ersteller')}}</th>
                            @endcan
                            <th>{{__('Festbetrag')}}</th>
                            <th>{{__('Rundenbetrag')}}</th>
                            <th>{{__('max. Betrag')}}</th>
                            <th>{{__('Projekte')}}</th>
                            <th>Betrag ({{number_format($summe, 2)}} €)</th>
                            <th></th>
                            </thead>
                            <tbody>
                            @foreach($sponsorings as $sponsoring)
                                <tr>
                                    <td>
                                        {{$sponsoring->sponsor->name}}
                                    </td>
                                    <td>
                                        {{optional($sponsoring->sponsorable)->name}}
                                    </td>
                                    @can('edit sponsoring')
                                        <td>
                                            {{$sponsoring->verwalter->name}}
                                        </td>
                                    @endcan
                                    <td>
                                        {{number_format($sponsoring->festBetrag,2)}} €
                                    </td>
                                    <td>
                                        {{number_format($sponsoring->rundenBetrag,2)}} €
                                    </td>
                                    <td>
                                        {{number_format($sponsoring->maxBetrag,2)}} €
                                    </td>
                                    <td>
                                        <div class="row">
                                            @foreach($sponsoring->projects as $project)
                                                        <small class="badge badge-info ml-1 p-2" title="Diese Spende unterstützt das das Projekt {{$project->name}}">{{$project->name}}</small>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        {{number_format($sponsoring->spende,2)}} €
                                    </td>
                                    <td>
                                        <form class="form-inline" method="post" action="{{url("sponsorings/$sponsoring->id")}}">
                                            @csrf
                                            @method("delete")
                                            <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            @cannot('edit sponsorings')
                                <tr>
                                    <td colspan="7 ">
                                        {{$sponsorings->links()}}
                                    </td>
                                </tr>
                            @endcannot
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@can('edit sponsorings')

    @push('js')
        <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
        <script>
            $(document).ready( function () {
                $('#sponsoringTable').DataTable();
            } );
        </script>


    @endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />

@endsection
@endcan