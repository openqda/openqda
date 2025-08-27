<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteProjectRequest;
use App\Http\Requests\ShowProjectRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

class ProjectController extends Controller
{
    /**
     * Display all the projects
     *
     * @return \Inertia\Response|never
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $visibleProjects = $this->visibleProjects();

        return Inertia::render('ProjectsList', [
            'projects' => $visibleProjects,
            'bgtl' => config('app.bgtl'),
            'bgtr' => config('app.bgtr'),
            'bgbr' => config('app.bgbr'),
            'bgbl' => config('app.bgbl'),
        ]);
    }

    protected function visibleProjects()
    {
        $user = Auth::user();

        // Combine the two types of projects
        $allProjectModels = $user->allRelatedProjects(false);

        // Transform projects for frontend
        return $allProjectModels->map(function ($project) use ($user) {
            return [
                'name' => $project->name,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
                'id' => $project->id,
                'isOwner' => $project->creating_user_id == $user->id,
                'isCollaborative' => ($project->team_id !== null),
                'isTrashed' => $project->trashed(),
                'sourcesCount' => $project->sources_count,
            ];
        })->filter(function ($project) {
            return ! $project['isTrashed'];
        })->toArray();

    }

    /**
     * Update project attributes - description or name
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProjectAttributes(UpdateProjectRequest $request, Project $project)
    {
        try {
            // Retrieve the value and type of the field to update from the request
            $newValue = $request->input('value');
            $fieldType = $request->input('type');

            // Update the project with the validated data
            $project->$fieldType = $newValue;
            $project->modifying_user_id = auth()->id();
            $project->save();

            return response()->json(['success' => true, 'message' => 'Project updated successfully']);
        } catch (\Exception $e) {
            // Handle exception
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }

    /**
     * Store a new project
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProjectRequest $request)
    {
        try {
            // Create a new project
            $project = new Project([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'creating_user_id' => Auth::id(),  // Set the current user as the creator
            ]);

            $project->save();

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'project' => [
                    'name' => $project->name,
                    'description' => $project->description,
                    'created_at' => $project->created_at,
                    'id' => $project->id,
                    'isOwner' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }

    /**
     * Show a particular project
     *
     * @return \Inertia\Response
     */
    public function show(ShowProjectRequest $request, Project $project)
    {
        $user = Auth::user();

        // to provide the project list we also need to load them here
        $visibleProjects = $this->visibleProjects();

        $publicCodebooks = collect();

        // Fetch codebooks for the project with creating user's information
        // Use withCount instead of with to avoid loading ALL codes
        $projectCodebooks = $project->codebooks()->withCount('codes')->with('creatingUser')->get();

        // Format the codebooks data to include the creating user's email
        $formattedCodebooks = $projectCodebooks->map(function ($codebook) {
            return [
                'id' => $codebook->id,
                'name' => $codebook->name,
                'description' => $codebook->description,
                'properties' => $codebook->properties,
                'codes_count' => $codebook->codes_count, // Use count instead of full codes
                'project_id' => $codebook->project_id,
                'creatingUserEmail' => $codebook->creatingUser->email ?? '',
            ];
        });

        $formattedPublicCodebooks = [];
        $projectId = $project->id;

        $sources = $project->sources()->with('creatingUser')->get();
        $inertiaData = [
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'id' => $projectId,
                'projectId' => $projectId,
                'codebooks' => $formattedCodebooks,
                'sources' => $sources->map(function ($source) {
                    return [
                        'id' => $source->id,
                        'name' => $source->name,
                        'type' => $source->type,
                        'user' => $source->creatingUser->name,
                        'userPicture' => $source->creatingUser->profile_photo_url,
                        'date' => $source->created_at->toDateString(),
                    ];
                }),
            ],
            'projects' => $visibleProjects,
            'userCodebooks' => $user->getCodebooksAsCreator($project->id),
            'publicCodebooks' => $formattedPublicCodebooks,
            'availableRoles' => array_values(Jetstream::$roles),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
            'hasTeam' => (bool) $project->team_id,
        ];

        $inertiaData['hasCodebooksTab'] = $request->has('codebookstab');

        return Inertia::render('ProjectOverview', $inertiaData);
    }

    public function getTeamData(Request $request, Project $project)
    {
        $user = Auth::user();
        $team = null;
        $teamOwner = false;
        $teamMembers = [];
        $permissions = [];

        if ($project->team_id) {
            $user->switchTeam($project->team);
            $team = $project->team->load('owner', 'users', 'teamInvitations');
            $teamOwner = $team->owner->email === $user->email;

            $teamMembers = $team->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });

            $permissions = [
                'canAddTeamMembers' => Gate::check('addTeamMember', $team),
                'canDeleteTeam' => Gate::check('delete', $team),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $team),
                'canUpdateTeam' => Gate::check('update', $team),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $team),
            ];
        }

        return response()->json([
            'team' => $team,
            'teamOwner' => $teamOwner,
            'teamMembers' => $teamMembers,
            'permissions' => $permissions,
            'hasTeam' => (bool) $project->team_id,
            'availableRoles' => array_values(Jetstream::$roles),
        ]);
    }

    /**
     * Delete a project
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(DeleteProjectRequest $request, $projectId)
    {
        try {
            // Retrieve the project and perform the deletion
            $project = Project::findOrFail($projectId);

            // Delete the project and trigger related events
            $project->delete();

            return to_route('projects.index')->with('message', 'Project Delete Successfully');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()], 500);
        }
    }
}
