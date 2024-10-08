@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header border-bottom  bg-info ">
                <div class="row">
                    <div class="col">
                        <h5 class="card-title  text-white">
                            Benutzerkonten
                        </h5>
                    </div>
                    <div class="col">
                        <p class=" pull-right">

                        </p>

                    </div>

                </div>
            </div>
            <div class="card-body">
                <table class="table table-hover" id="userTable">
                    <thead>
                        <tr>
                            <td></td>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Rechte</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <a href="{{url('/users/').'/'.$user->id}}" class="btn-link">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                                <td>
                                    {{$user->name}}
                                </td>
                                <td>
                                    {{$user->email}}
                                </td>
                                <td>
                                    @foreach($user->permissions as $permission)
                                        {{$permission->name}}
                                        @if(!$loop->last)
                                        ,
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('js')
 <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
 <script>
     $(document).ready( function () {
         $('#userTable').DataTable();
     } );
 </script>


 @can('edit user')
     <script src="{{asset('js/plugins/sweetalert2.all.min.js')}}"></script>


 @endcan
@endpush

@section('css')
    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" />

@endsection
