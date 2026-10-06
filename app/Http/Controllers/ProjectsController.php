<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Projects;
use App\Support\CurrentEvent;

class ProjectsController extends Controller
{
    public function index()
    {
        $event = app(CurrentEvent::class)->get();

        $projekte = $event
            ? $event->projects()->orderBy('name')->get()
            : Projects::orderBy('name')->get();

        $view = auth()->check() ? 'projects.index' : 'public.projekte';

        return view($view, [
            'projekte' => $projekte,
            'weitere' => auth()->user()?->can('edit projekt')
                ? Projects::whereNotIn('id', $projekte->pluck('id'))->orderBy('name')->get()
                : collect(),
            'event' => $event,
        ]);
    }

    public function create()
    {
        return view('projects.form', ['project' => new Projects]);
    }

    public function store(ProjectRequest $request)
    {
        $project = Projects::create($request->safe()->only(['name', 'description']));
        $this->bildSpeichern($request, $project);

        if ($request->boolean('im_aktuellen_lauf') && ($event = app(CurrentEvent::class)->get())) {
            $event->projects()->syncWithoutDetaching([$project->id]);
        }

        return $this->erfolg('Projekt erstellt.', route('projects.index'));
    }

    public function edit(Projects $project)
    {
        return view('projects.form', ['project' => $project]);
    }

    public function update(ProjectRequest $request, Projects $project)
    {
        $project->update($request->safe()->only(['name', 'description']));
        $this->bildSpeichern($request, $project);

        if ($event = app(CurrentEvent::class)->get()) {
            $request->boolean('im_aktuellen_lauf')
                ? $event->projects()->syncWithoutDetaching([$project->id])
                : $event->projects()->detach($project->id);
        }

        return $this->erfolg('Projekt gespeichert.', route('projects.index'));
    }

    public function destroy(Projects $project)
    {
        if ($project->sponsorings()->exists()) {
            return $this->fehler('Das Projekt wurde bereits gesponsert und kann nicht gelöscht werden. Entfernen Sie es stattdessen aus dem aktuellen Lauf.');
        }

        $project->events()->detach();
        $project->delete();

        return $this->erfolg('Projekt gelöscht.', route('projects.index'));
    }

    private function bildSpeichern(ProjectRequest $request, Projects $project): void
    {
        if ($request->hasFile('bild')) {
            $project->addMediaFromRequest('bild')->toMediaCollection('images');
        }
    }
}
