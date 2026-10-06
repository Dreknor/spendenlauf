<?php

namespace App\Providers;

use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Teams;
use App\Models\User;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentEvent::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            'laeufer' => Laeufer::class,
            'team' => Teams::class,
            'projekt' => Projects::class,
            'user' => User::class,
        ]);

        View::composer('*', function ($view) {
            $view->with('aktuellesEvent', app(CurrentEvent::class)->get());
        });

        Gate::define('viewPulse', fn (User $user) => $user->can('edit user'));
    }
}
