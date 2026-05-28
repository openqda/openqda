<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VariableController extends Controller
{
    public function store(Request $request, string $project)
    {
        $variable = Variable::create([
            ...$validated,
            'project_id' => $project,
            'guid' => (string) Str::uuid(),
        ]);

        return response()->json([
            'message' => 'Variable created successfully.',
            'variable' => $variable,
        ], 201);
    }
}
