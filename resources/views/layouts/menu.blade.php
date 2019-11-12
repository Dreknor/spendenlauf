<ul class="nav">
    @if(auth()->check())
        <li class="@if(request()->segment(1)=="home") active @endif">
            <a href="{{url('/home')}}">
                <i class="fas fa-home"></i>
                <p>{{__('Start')}}</p>
            </a>
        </li>
        <li class="@if(request()->segment(1)=="laeufer") active @endif">
            <a href="{{url('/laeufer')}}">
                <i class="fas fa-running"></i>
                <p>{{__('Läufer')}}</p>
            </a>
        </li>
        <li class="@if(request()->segment(1)=="teams") active @endif">
            <a href="{{url('/teams')}}">
                <i class="fas fa-user-friends"></i>
                <p>{{__('Teams')}}</p>
            </a>
        </li>
        <li class="@if(request()->segment(1)=="sponsoren") active @endif">
            <a href="{{url('/sponsoren')}}">
                <i class="fas fa-hand-holding-usd"></i>
                <p>{{__('Spender')}}</p>
            </a>
        </li>
        <li class="@if(request()->segment(1)=="sponsorings") active @endif">
            <a href="{{url('/sponsorings')}}">
                <i class="fas fa-euro-sign"></i>
                <p>{{__('Spenden')}}</p>
            </a>
        </li>
        <li class="@if(request()->segment(1)=="projects") active @endif">
            <a href="{{url('/projects')}}">
                <i class="fas fa-cog"></i>
                <p>{{__('Projekte')}}</p>
            </a>
        </li>
        <li class="divider"></li>


        @can('edit user')
            <li class="@if(request()->segment(1)=="users") active @endif">
                <a href="{{url('/users')}}">
                    <i class="fas fa-users"></i>
                    <p>User</p>
                </a>
            </li>
        @endcan

        @can('import export')
            <li class="@if(request()->segment(1)=="Export") active @endif">
                <a href="{{url('/export/laeufer')}}">
                    <i class="fas fa-file-export"></i>
                    Export Läufer
                </a>
            </li>
            <li class="@if(request()->segment(1)=="Export") active @endif">
                <a href="{{url('/export/sponsoren')}}">
                    <i class="fas fa-file-export"></i>
                    Export Spender
                </a>
            </li>
            <li class="@if(request()->segment(1)=="Export") active @endif">
                <a href="{{url('/export/projects')}}">
                    <i class="fas fa-file-export"></i>
                    Export Projekte
                </a>
            </li>
            <li class="@if(request()->segment(1)=="Export") active @endif">
                <a href="{{url('/import/runden')}}">
                    <i class="fas fa-upload"></i>
                    Import Runden
                </a>
            </li>
        @endcan

    @endif
</ul>