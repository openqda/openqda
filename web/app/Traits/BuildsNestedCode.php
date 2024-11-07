<?php

namespace App\Traits;

use App\Models\Code;

trait BuildsNestedCode
{
    /**
     * Build nested structure for each code including its children and selections.
     *
     * @param  int|null  $sourceId  Optional source ID to filter selections
     */
    protected function buildNestedCode(Code $code, $sourceId = null): array
    {
        $nestedCode = [
            'id' => $code->id,
            'name' => $code->name,
            'color' => $code->color,
            'codebook' => $code->codebook->id,
            'description' => $code->description ?? '',
            'children' => [],
            'text' => $this->getCodeSelections($code, $sourceId),
        ];

        foreach ($code->children as $child) {
            $nestedCode['children'][] = $this->buildNestedCode($child, $sourceId);
        }

        return $nestedCode;
    }

    /**
     * Get selections for a code, optionally filtered by source.
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
