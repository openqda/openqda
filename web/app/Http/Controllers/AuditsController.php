<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuditFilterRequest;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Class AuditsController
 *
 * Handles the retrieval and filtering of audit logs for both user-wide
 * and project-specific contexts.
 */
class AuditsController extends Controller
{
    /**
     * @var AuditService
     */
    protected $auditService;

    /**
     * AuditsController constructor.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Retrieve filtered and paginated audits for the authenticated user.
     */
    public function index(AuditFilterRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $filters = $request->getFilters();

            $allAudits = $user->getAllAudits($filters);
            $paginator = $this->auditService->paginateAudit($allAudits, $request);

            return response()->json([
                'success' => true,
                'audits' => $paginator,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user audits', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'data' => $filters,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Unable to fetch audit history. Please try again.'),
            ], 500);
        }
    }

    /**
     * Retrieve filtered and paginated audits for a specific project.
     */
    public function projectAudits(AuditFilterRequest $request, Project $project): JsonResponse
    {
        try {
            $allAudits = $this->auditService->getProjectAudits($project);
            $filteredAudits = $this->auditService->filterAudits($allAudits, $request->getFilters());
            $paginator = $this->auditService->paginateAudit($filteredAudits, $request);

            return response()->json([
                'success' => true,
                'audits' => $paginator,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching project audits', [
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Unable to fetch project audit history. Please try again.'),
            ], 500);
        }
    }
}
