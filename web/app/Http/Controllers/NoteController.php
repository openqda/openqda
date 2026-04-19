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

        if ($note->visibility === 0 && $note->creating_user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json(['note' => $note]);
    }

    /**
     * Store a new note.
     */
    public function store(StoreNoteRequest $request, Project $project): JsonResponse
    {
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

        if ($note->creating_user_id !== Auth::id()) {
            abort(403, 'You can only edit your own notes.');
        }

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

        if ($note->creating_user_id !== Auth::id()) {
            abort(403, 'You can only delete your own notes.');
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully.']);
    }
}
