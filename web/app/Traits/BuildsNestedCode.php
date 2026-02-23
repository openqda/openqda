<?php

namespace App\Traits;

use App\Models\Code;

trait BuildsNestedCode
{
    /**
     * Build nested structure for each code including its children and selections.
     *
     * @param  int|null  $sourceId  Optional source ID to filter selections
     * @param  bool  withSelections  Optional flag to include selections in the output
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

    protected function getCodeSelectionCount(Code $code, $sourceId = null): int
    {
        return $sourceId
            ? $code->selectionsForSource($sourceId)->count()
            : $code->selections()->count();
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

        return $selections->map(function ($s) {
            return [
                'id' => $s->id,
                'text' => $s->text,
                'start' => $s->start_position,
                'end' => $s->end_position,
                'createdBy' => $s->creating_user_id,
                'createdAt' => $s->created_at,
                'updatedAt' => $s->updated_at,
                'source_id' => $s->source_id,
            ];
        })->toArray();
    }
}
