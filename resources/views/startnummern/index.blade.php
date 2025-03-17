@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    {{__('noch verfügbare Startnummern')}}
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{__('Startnummer')}}</th>
                            <th>{{__('Aktion')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($startnummern as $startnummer)
                            <tr>
                                <td>{{$startnummer->startnummer}}</td>
                                <td>
                                    <a href="{{url("/startnummern/$startnummer->id/delete")}}" class="btn btn-danger">
                                        {{__('Startnummer löschen')}}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <form action="{{url('startnummern')}}" method="post" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                        <label for="startnummer">{{__('Startnummer ab ... erstellen')}}</label>
                        <input type="text" class="form-control" name="startnummer_start" required>
                    </div>
                    <div class="form-group">
                        <label for="startnummer">{{__('Startnummer bis ...')}}</label>
                        <input type="text" class="form-control" name="startnummer_ende" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        {{__('Startnummer hinzufügen')}}
                    </button>
                </form>
            </div>
        </div>





@endsection
