<?php

namespace App\Traits;

use App\Models\Code;

trait BuildsNestedCode
{
    /**
     * Build nested structure for each code including its children and selections.
     *
     * @param  int|null  $sourceId  Optional source ID to filter selections
     * @param  bool  $withSelections  Optional flag to include selections in the output
     */
    protected function buildNestedCode(Code $code, $sourceId = null, bool $withSelections = false): array
    {
        $selections = $withSelections ? $this->getCodeSelections($code, $sourceId) : [];
        $selectionsCount = $withSelections ? count($selections) : $this->getCodeSelectionCount($code, $sourceId);
        $nestedCode = [
            'id' => $code->id,
            'name' => $code->name,
            'color' => $code->color,
            'codebook' => $code->codebook->id,
            'description' => $code->description ?? '',
            'children' => [],
            'text' => $selections,
            'selectionsCount' => $selectionsCount,
        ];

        foreach ($code->children as $child) {
            $nestedCode['children'][] = $this->buildNestedCode($child, $sourceId, $withSelections);
        }

        return $nestedCode;
    }

    /**
     * Returns count of selection by a given code and optionally source.
     *
     * @param  Code  $code  The code for which to retrieve selections
     * @param  int|null  $sourceId  Optional source ID to filter selections
     * @return int Count of selections found
     */
    protected function getCodeSelectionCount(Code $code, $sourceId = null): int
    {
        if ($sourceId === null) {
            // If the 'selections' relation is already eager loaded, use the in-memory collection
            if ($code->relationLoaded('selections')) {
                return $code->selections->count();
            }

            // Fallback: count via query
            return $code->selections()->count();
        }

        // For source-specific counts, fall back to querying the scoped relation
        return $code->selectionsForSource($sourceId)->count();
    }

    /**
     * Get selections for a code, optionally filtered by source.
     *
     * @param  Code  $code  The code for which to retrieve selections
     * @param  int|null  $sourceId  Optional source ID to filter selections
     * @return array Array of selections with relevant details
     */
    protected function getCodeSelections(Code $code, $sourceId = null): array
    {
        $selections = $sourceId
            ? $code->selectionsForSource($sourceId)->get()
            : $code->selections;

        // TODO remove mapping, have clients map
        return $selections->map(function ($s) {
            return [
                'id' => $s->id,
                'text' => $s->text,
                'start' => $s->start_position,
                'end' => $s->end_position,
                'code_id' => $s->code_id,
                'createdBy' => $s->creating_user_id,
                'createdAt' => $s->created_at,
                'updatedAt' => $s->updated_at,
                'source_id' => $s->source_id,
            ];
        })->toArray();
    }
}
