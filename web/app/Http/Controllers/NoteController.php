<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * List all notes for a project.
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('viewAny', [Note::class, $project]);

        $notes = $project->notes()
            ->where(function ($query) {
                $query->where('creating_user_id', Auth::id())
                    ->orWhere('visibility', 1);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['notes' => $notes]);
    }

    /**
     * Get a single note.
     */
    public function show(Project $project, Note $note): JsonResponse
    {
        if ($note->project_id !== $project->id) {
            abort(404);
        }

        $this->authorize('view', $note);

        return response()->json(['note' => $note]);
    }

    /**
     * Store a new note.
     */
    public function store(StoreNoteRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Note::class, $project]);

        $data = $request->validated();
        $data['project_id'] = $project->id;
        $data['creating_user_id'] = Auth::id();

        $note = Note::create($data);

        return response()->json(['message' => 'Note created successfully.', 'note' => $note], 201);
    }

    /**
     * Update an existing note.
     */
    public function update(UpdateNoteRequest $request, Project $project, Note $note): JsonResponse
    {
        if ($note->project_id !== $project->id) {
            abort(404);
        }

        $this->authorize('update', $note);

        $note->update($request->validated());

        return response()->json(['message' => 'Note updated successfully.', 'note' => $note]);
    }

    /**
     * Delete a note.
     */
    public function destroy(Project $project, Note $note): JsonResponse
    {
        if ($note->project_id !== $project->id) {
            abort(404);
        }

        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully.']);
    }
}
