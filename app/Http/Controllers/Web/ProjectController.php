<?php

namespace App\Http\Controllers\Web;

use App\Actions\Project\CreateProject;
use App\Actions\Project\DeleteProject;
use App\Actions\Project\UpdateProject;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::with('client')->orderBy('name')->paginate(15);

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        $clients = Client::orderBy('name')->get();

        return view('projects.create', compact('clients'));
    }

    public function store(StoreProjectRequest $request, CreateProject $createProject): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = $createProject->handle($request->validated());

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        $clients = Client::orderBy('name')->get();

        return view('projects.edit', compact('project', 'clients'));
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $updateProject): RedirectResponse
    {
        $this->authorize('update', $project);

        $updateProject->handle($project, $request->validated());

        return redirect()->route('projects.show', $project);
    }

    public function destroy(Project $project, DeleteProject $deleteProject): RedirectResponse
    {
        $this->authorize('delete', $project);

        $deleteProject->handle($project);

        return redirect()->route('projects.index');
    }
}
