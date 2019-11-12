@extends('layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                {{__('Team erstellen')}}
            </h5>
        </div>

        <div class="card-body">
            <form action="{{url('/teams')}}" method="post" class="form form-horizontal">
                @csrf
                <div class="row">
                    <div class="col">
                        <p class="alert alert-info">
                            <b>Hinweis: </b> Teams, die öffentlich sind, können von anderen läufern gesehen und diesen beigetreten werden. Bei nicht öffentlichen Teams muss der Ersteller des teams auch alle läufer anmelden um diese in das team aufnehmen zu können.
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label>{{__('Name des Teams')}}</label>
                            <input type="text" class="form-control border-input"  name="name" value="{{old('name')}}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label>{{__('Name des Teams')}}</label>
                            <select type="text" class="custom-select"  name="open" required>
                                <option value="1">
                                    {{__('öffentlich')}}
                                </option>
                                <option value="0">
                                    {{__('nicht öffentlich')}}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                            {{__('Team erstellen')}}
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('css')


@endpush

@push('js')

@endpush