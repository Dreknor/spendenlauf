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
                    @if(config('spendenlauf.help_name') != "")
                        <div class="card-body border-bottom">
                            <p class="">
                                {{config('spendenlauf.help_percent')}} % werden für das Hilfsprojekt {{config('spendenlauf.help_name')}} verwendet.
                            </p>
                        </div>
                    @endif
                    @if(count($projects) > 0)
                        <div class="card-body">
                            <div class="card-deck">
                                @foreach($projects as $project)
                                        <div class="card border">
                                            <div class="card-body">
                                                    <p class="small text-muted">
                                                        Spenden: {{$project->sponsorings->count()}}
                                                    </p>

                                                <h5 class="card-title">{{$project->name}}</h5>
                                                <p class="card-text">{!! $project->description !!}</p>


                                                @if($project->getFirstMedia('images') != null)
                                                    <img src="{{url('/image/'.$project->getFirstMedia('images')->id)}}" class="card-img-top" alt="">
                                                @endif
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
