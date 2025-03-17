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
            <div class="col-auto">
                @if($project->getFirstMedia('images') != null)
                    <img src="{{url('/image/'.$project->getFirstMedia('images')->id)}}" class="img" width="100px" alt="{{$project->name}}"  title="Diese Spende unterstützt das das Projekt {{$project->name}}">
                @else
                    <small class="badge badge-primary" title="Diese Spende unterstützt das das Projekt {{$project->name}}">{{$project->name}}</small>
                @endif
            </div>
        @endforeach
        <div class="col-auto ml-1">
            @if(config('spendenlauf.help_name') != "")
                <small class="badge badge-info ml-1 p-2" title="Diese Spende unterstützt das das Projekt {{config('spendenlauf.help_name')}}">{{config('spendenlauf.help_name')}}</small>
            @endif
        </div>

    </div>

</li>
