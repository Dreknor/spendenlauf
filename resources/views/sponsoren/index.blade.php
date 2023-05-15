@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-info">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <h5 class="card-title text-white">
                            {{__('Spender-Übersicht')}}
                        </h5>
                    </div>
                    <div class="col-md-2 col-sm-6 content-center">
                        <a href="{{url('sponsoren/create')}}" class="btn btn-success">
                            {{__('Spender erstellen')}}
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-6 content-center">
                        @can('send mail')
                            @if(Carbon\Carbon::now()->gt(config('config.spendenlauf.date')))
                                <a href="{{url('sponsor/sendMail/all')}}" class="btn btn-warning">
                                    <i class="far fa-paper-plane"></i>
                                    {{__('Mailing versenden')}}
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>

            </div>
            <div class="card-body">
                @if(count($sponsoren)<1)
                    <p>
                        {{__('Sie haben bisher keine Spender erstellt.')}}
                    </p>
                @else
                    <div class="table-responsive-md  ">
                        <table class="table table-striped" id="sponsorTable">
                            <thead>
                            <th>{{__('Sponsorname')}}</th>
                            <th>{{__('E-Mail')}}</th>
                            <th>
                                @can('edit sponsoren')
                                    {{__('Spenden')}}
                                @else
                                    {{__('verwaltete Spenden')}}
                                @endcan
                            </th>
                            @can('edit sponsoren')
                                <th>{{__('Ersteller')}}</th>
                            @endcan
                            <th></th>
                            @can('send mail')
                                <th></th>
                            @endcan
                            </thead>
                            <tbody>
                            @foreach($sponsoren as $sponsor)
                                <tr>
                                    <td>
                                        {{$sponsor->name}}
                                    </td>
                                    <td>
                                        {{optional($sponsor)->email}}
                                    </td>
                                    <td>
                                        @can('edit sponsoren')
                                            {{$sponsor->sponsorings->count()}}
                                        @else
                                            {{$sponsor->sponsorings->where('verwaltet_von', auth()->user()->id)->count()}}
                                        @endcan
                                    </td>
                                    @can('edit sponsoren')
                                        <td>
                                            @foreach($sponsor->verwalter as $verwalter)
                                                {{$verwalter->name}} @if(!$loop->last), @endif
                                            @endforeach
                                        </td>
                                    @endcan
                                    <td>
                                        <a href="{{url('sponsoren/'.$sponsor->id.'/edit')}}" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit d-block d-md-none "></i>
                                            <div class="d-none d-md-block">
                                                {{__('anzeigen')}}
                                            </div>
                                        </a>
                                    </td>
                                    @can('send mail')
                                        @if($sponsor->email != "")
                                            @if($sponsor->mail_send == null)
                                                <td>
                                                    <a href="{{url("sponsor/sendMail/$sponsor->id")}}" class="btn btn-warning">
                                                        <i class="far fa-paper-plane"></i>
                                                        {{__('Mailing versenden')}}
                                                    </a>
                                                </td>
                                            @else
                                                <td>
                                                    E-Mail versandt am {{$sponsor->mail_send}}
                                                </td>
                                            @endif
                                        @else
                                            <td>
                                                Keine Email
                                            </td>
                                        @endif
                                    @endcan
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            @cannot('edit sponsoren')
                                <tr>
                                    <td colspan="5 ">
                                        {{$sponsoren->links()}}
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

@can('edit sponsoren')

    @push('js')
        <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
        <script>
            $(document).ready( function () {
                $('#sponsorTable').DataTable();
            } );
        </script>


    @endpush

    @section('css')
        <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />

    @endsection
@endcan
