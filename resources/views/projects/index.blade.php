@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info">
                        <div class="row">
                            <div class=" col-sm-9 col-md-10">
                                <h5 class="card-title  text-white">
                                    {{__('Spendenprojekte')}}
                                </h5>
                            </div>
                            @can('edit projekt')
                                <div class="col-sm-3 col-md-2 pull-right">
                                    <a href="{{url('projects/create')}}" class="btn btn-success">
                                        <i class="fas fa-plus-square d-block d-md-none"></i>
                                        <div class="d-none d-md-block">
                                            {{__('Projekt anlegen')}}
                                        </div>
                                    </a>
                                </div>
                            @endcan
                        </div>

                    </div>
                    @if(count($projects) > 0)
                        <div class="card-body">
                            <div class="card-columns">
                                @foreach($projects as $project)
                                        <div class="card border">
                                            @if($project->getFirstMedia('images') != null)
                                                <img src="{{url('/image/'.$project->getFirstMedia('images')->id)}}" class="card-img-top" alt="...">
                                            @endif
                                            <div class="card-body">
                                                <h5 class="card-title">{{$project->name}}</h5>
                                                <p class="card-text">{!! $project->description !!}</p>
                                            </div>
                                            <div class="card-footer">
                                                <div class="row">
                                                    <p class="small text-muted">Spenden: {{$project->sponsorings->count()}}
                                                    </p>
                                                </div>
                                                @can('edit projekt')
                                                    <a href="{{url("projects/$project->id/edit")}}" class="btn btn-warning btn-block">
                                                        {{__('bearbeiten')}}
                                                    </a>
                                                @endcan

                                            </div>
                                        </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                                <div class="card-body bg-warning">
                                    {{__('Es wurden bisher keine Projekte angelegt')}}
                                </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection