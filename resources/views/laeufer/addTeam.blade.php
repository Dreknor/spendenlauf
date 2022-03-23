@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    {{__('Läufer - Team - Zuordnung')}}
                </h5>
                <p class="card-subtitle mb-2 text-muted">{{__('Welchem Team soll folgende/r Läufer/in zugeordnet werden:')}} {{$laeufer->name}}</p>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p>
                        {{__('Hinweis:')}} {{__('Fügen Sie den Läufer/die Läuferin einem anderen, offenen Team hinzu, wird der Ersteller des Team darüber informiert.')}}
                    </p>
                </div>
                <form class="form-horizontal" action="{{url("laeufer/$laeufer->id/addTeam")}}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col">
                            <select class="custom-select" name="team_id">
                                @foreach($teams as $team)
                                    <option value="{{$team->id}}">{{$team->name}}  @if($team->verwaltet_von != auth()->user()->id)(öffentliches Team - {{$team->laeufer->count()}} Läufer)@endif</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col">
                            <button type="submit" class="btn btn-success btn-block">{{__('zuordnen')}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
