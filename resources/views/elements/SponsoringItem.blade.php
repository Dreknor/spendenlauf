<li class="list-group-item ">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="row">
                {{$sponsoring->sponsor->name}}
            </div>
            <div class="row">
                ({{number_format($sponsoring->festBetrag,2)}} € /{{number_format($sponsoring->rundenBetrag,2)}} € /{{number_format($sponsoring->maxBetrag,2)}}€)
            </div>
            <div class="row">
                @if($sponsoring->type == "Teams") Team @else Läufer @endif{{$sponsoring->sponsorable->name}}
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
                {{__('Spendensumme: ')}} {{number_format($sponsoring->spende($sponsoring->sponsorable->runden),2)}} €
        </div>
    </div>

    <div class="row">
        @foreach($sponsoring->projects as $project)
            <div class="col-md-2 col-sm-6">
                @if($project->getFirstMedia('images') != null)
                    <img src="{{url('/image/'.$project->getFirstMedia('images')->id)}}" class="img-fluid" alt="{{$project->name}}"  title="Diese Spende unterstützt das das Projekt {{$project->name}}">
                @else
                    <small class="badge badge-primary" title="Diese Spende unterstützt das das Projekt {{$project->name}}">{{$project->name}}</small>
                @endif
            </div>
        @endforeach
    </div>

</li>