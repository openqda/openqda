<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AuditService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditsController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get all audits from all projects.
        $allAudits = Auth::user()->getAllAudits();

        // Set pagination variables
        $perPage = 2;
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

    /**
     * @return \Illuminate\Http\JsonResponse
     */
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
}
