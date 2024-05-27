<?php

namespace App\Http\Controllers;

use App\Models\Codebook;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if (! Gate::allows('viewAny', Project::class)) {
            return abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        // Fetch projects directly created by the user
        $ownedProjects = $user->projects()->withTrashed()->get();

        // Fetch projects where the user is invited through a team
        $invitedProjects = $user->invitedTeamProjects();

        // Combine the two types of projects
        $allProjectModels = $ownedProjects->concat($invitedProjects);

        // Transform projects for frontend
        $visibleProjects = $allProjectModels->map(function ($project) use ($user) {
            return [
                'name' => $project->name,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'id' => $project->id,
                'isOwner' => $project->creating_user_id == $user->id,
                'isCollaborative' => ($project->team_id !== null),
                'isTrashed' => $project->trashed(),
            ];
        })->filter(function ($project) {
            return ! $project['isTrashed'];
        })->toArray();

        // Fetch all related audits using the User model's method
        $allAudits = $user->getAllAudits()->values();

        $paginator = app(AuditService::class)->paginateAudit($allAudits, $request);

        return Inertia::render('ProjectsList', [
            'projects' => $visibleProjects,
            'audits' => $paginator,
            'bgtl' => config('app.bgtl'),
            'bgtr' => config('app.bgtr'),
            'bgbr' => config('app.bgbr'),
            'bgbl' => config('app.bgbl'),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProjectAttributes(Request $request, Project $project)
    {

        try {
            // Retrieve the value and type of the field to update from the request
            $newValue = $request->input('value');
            $fieldType = $request->input('type');

            // Validate if the user is the creator of the project
            if (auth()->id() !== $project->creating_user_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized']);
            }

            // Check the field type and update accordingly
            if (in_array($fieldType, ['name', 'description'])) {
                $project->$fieldType = $newValue;
                $project->modifying_user_id = auth()->id();

                $project->save();
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid field type']);
            }

            return response()->json(['success' => true, 'message' => 'Project updated successfully']);
        } catch (\Exception $e) {
            // Handle exception
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        if (! Gate::allows('create', Project::class)) {
            abort(403, 'Unauthorized action.');
        }
        try {
            // Validate the request
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            // Create a new project
            $project = new Project([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'creating_user_id' => Auth::id(),  // Assuming you want to set the current user as the creator
            ]);

            $project->save();

            return response()->json(['success' => true, 'message' => 'Project created successfully', 'project' => ['name' => $project->name, 'description' => $project->description, 'created_at' => $project->created_at, 'id' => $project->id, 'isOwner' => true]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }

    public function show(Request $request, Project $project)
    {
        if (! Gate::allows('view', $project)) {
            return abort(403, 'Unauthorized action.');
        }

        $user = Auth::user();

        // Retrieve audits related to the project
        $allAudits = app(AuditService::class)->getAudits($project);

        $paginator = app(AuditService::class)->paginateAudit($allAudits, $request);
        $publicCodebooks = Codebook::where('properties->sharedWithPublic', true)
            ->with('codes', 'creatingUser')
            ->where('project_id', '!=', $project->id)
            ->get();
        // Fetch codebooks for the project with creating user's information
        $projectCodebooks = $project->codebooks()->with('codes', 'creatingUser')->get();

        // Format the codebooks data to include the creating user's email
        $formattedCodebooks = $projectCodebooks->map(function ($codebook) {
            return [
                'id' => $codebook->id,
                'name' => $codebook->name,
                'properties' => $codebook->properties,
                'codes' => $codebook->codes,
                'project_id' => $codebook->project_id,
                // Include other codebook attributes as needed
                'creatingUserEmail' => $codebook->creatingUser->email ?? '', // Include the creating user's email
            ];
        });

        $formattedPublicCodebooks = $publicCodebooks->map(function ($codebook) {
            return [
                'id' => $codebook->id,
                'name' => $codebook->name,
                'properties' => $codebook->properties,
                'codes' => $codebook->codes,
                'project_id' => $codebook->project_id,
                // Include other codebook attributes as needed
                'creatingUserEmail' => $codebook->creatingUser->email ?? '', // Include the creating user's email
            ];
        });

        // Initialize data for Inertia response
        $inertiaData = [
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'id' => $project->id,
                'projectId' => $project->id,
                'codebooks' => $formattedCodebooks,
            ],
            'userCodebooks' => auth()->user()->getCodebooksAsCreator($project->id),
            'publicCodebooks' => $formattedPublicCodebooks,
            'audits' => $paginator,
            'availableRoles' => array_values(Jetstream::$roles),
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
            'hasTeam' => (bool) $project->team_id,  // Flag to indicate if a team exists for this project
        ];

        // Initialize empty teamMembers array
        $teamMembers = [];

        // Check if the project has a team
        if ($project->team_id) {
            $user->switchTeam($project->team);
            // Get the team of the project
            $team = $project->team->load('owner', 'users', 'teamInvitations');
            $inertiaData['teamOwner'] = $team->owner->email === auth()->user()->email;

            // Get all users that belong to this team
            $teamMembers = $team->users;

            // Add team and permissions data to the Inertia return data to be shown in the fo
            $inertiaData['team'] = $team;
            $inertiaData['permissions'] = [
                'canAddTeamMembers' => Gate::check('addTeamMember', $team),
                'canDeleteTeam' => Gate::check('delete', $team),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $team),
                'canUpdateTeam' => Gate::check('update', $team),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $team),
            ];
            $inertiaData['teamMembers'] = $teamMembers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            });
        }

        // Render the Inertia view with the compiled data
        // Check if the request has 'codebookstab' and set a flag
        $hasCodebooksTab = $request->has('codebookstab');

        // Add the flag to the Inertia data
        $inertiaData['hasCodebooksTab'] = $hasCodebooksTab;

        // Render the Inertia view with the compiled data
        return Inertia::render('ProjectOverview', $inertiaData);
    }

    public function loadMoreAudits(Request $request, Project $project)
    {

        $allAudits = app(AuditService::class)->getAudits($project);

        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $total = count($allAudits);
        $start = ($currentPage - 1) * $perPage;
        $currentData = $allAudits->slice($start, $perPage);

        $paginator = new LengthAwarePaginator($currentData, $total, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return response()->json(['audits' => $paginator]);
    }

    public function loadAllProjectsAudits(Request $request)
    {
        // Get all audits from all projects. You'd replace 'user' with the actual user model instance.
        $allAudits = Auth::user()->getAllAudits();

        // Set pagination variables
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $total = count($allAudits);

        // Get the current page data
        $start = ($currentPage - 1) * $perPage;
        $currentData = $allAudits->slice($start, $perPage);

        // Create a paginator instance
        $paginator = new LengthAwarePaginator($currentData, $total, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return response()->json(['audits' => $paginator]);
    }

    public function destroy(Request $request, $projectId)
    {
        try {
            // Authorization checks
            $project = Project::findOrFail($projectId);
            if (! Gate::allows('delete', $project)) {
                return response()->json(['success' => false, 'message' => 'Not authorized to delete this project']);
            }

            // Delete the project. The ProjectDeleting event will be fired,
            // which will trigger the DeleteRelatedSources listener to delete all sources and teams.
            $project->delete();

            return to_route('projects.index')->with('message', 'Project Delete Successfully');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()]);
        }
    }
}
