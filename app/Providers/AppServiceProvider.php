<?php

namespace App\Providers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'local') {
            $this->app->register('Kurt\Repoist\RepoistServiceProvider');
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });

        \Illuminate\Support\Collection::macro('sortByMulti', function (array $keys) {
            $currentIndex = 0;

            $keys = array_map(function ($key, $sort) {
                return ['key' => $key, 'sort' => $sort];
            }, array_keys($keys), $keys);

            $sortBy = function (Collection $collection) use (&$currentIndex, $keys, &$sortBy) {
                if ($currentIndex >= count($keys)) {
                    return $collection;
                }
                $key = $keys[$currentIndex]['key'];
                $sort = $keys[$currentIndex]['sort'];
                $sortFunc = $sort === 'DESC' ? 'sortByDesc' : 'sortBy';
                $currentIndex++;

                return $collection->$sortFunc($key)->groupBy($key)->map($sortBy)->ungroup();
            };

            /** @var \Illuminate\Support\Collection $this */
            return $sortBy($this);
        });

        /**
         * Ungroup Previously Grouped Collection
         */
        \Illuminate\Support\Collection::macro('ungroup', function () {
            $newCollection = \Illuminate\Support\Collection::make([]);
            /** @var \Illuminate\Support\Collection $this */
            $this->each(function ($item) use (&$newCollection) {
                $newCollection = $newCollection->merge($item);
            });

            return $newCollection;
        });
    }
}
