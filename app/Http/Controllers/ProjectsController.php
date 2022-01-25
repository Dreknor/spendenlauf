<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Model\Projects;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('projects.index', [
            'projects'  => Projects::all()->load('sponsorings'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('edit projekt')) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        return view('projects.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProjectRequest $request)
    {
        if (! auth()->user()->can('edit projekt')) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        $project = new Projects($request->all());
        $project->save();
        if ($request->hasFile('files')) {
            $project->addMediaFromRequest('files')->toMediaCollection('images');
        }

        return redirect(url('projects'))->with([
            'type'   => 'success',
            'Meldung'    => __('Projekt erstellt'),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Model\Projects  $projects
     * @return \Illuminate\Http\Response
     */
    public function show(Projects $projects)
    {
        return $this->index();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Model\Projects  $projects
     * @return \Illuminate\Http\Response
     */
    public function edit(Projects $project)
    {
        if (! auth()->user()->can('edit projekt')) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        return view('projects.edit', [
            'project'   => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Model\Projects  $projects
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Projects $project)
    {
        if (! auth()->user()->can('edit projekt')) {
            return redirect()->back()->with([
                'type'  => 'danger',
                'Meldung'   => __('Berechtigung fehlt'),
            ]);
        }

        $project->update([
            'name'  => $request->name,
            'description'   => $request->description,
        ]);

        if ($request->hasFile('files')) {
            $project->clearMediaCollection('images');
            $project->addMediaFromRequest('files')->toMediaCollection('images');
        }

        return redirect(url('projects'))->with([
            'type'  => 'success',
            'Meldung'   => __('Projekt bearbeitet'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Model\Projects  $projects
     * @return \Illuminate\Http\Response
     */
    public function destroy(Projects $projects)
    {
        return redirect()->back();
    }
}
