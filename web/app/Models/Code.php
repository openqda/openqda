<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Class Code
 */
class Code extends Model implements Auditable
{
    use HasFactory, HasUuids;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = false;

    /**
     * @var mixed|string|null
     */
    protected $fillable = [
        'id',
        'name',
        'color',
        'description',
        'parent_id',
    ];

    protected $primaryKey = 'id';

    public $incrementing = false;

    /**
     * get the codebook from which the code is created
     *
     * @return BelongsTo
     */
    public function codebook()
    {
        return $this->belongsTo(Codebook::class);
    }

    /**
     * get all the selected text inside this code <- all of them among different documents
     *
     * @return HasMany
     */
    public function selections()
    {
        return $this->hasMany(Selection::class);
    }

    /**
     * This will give model's Parent
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * This will give model's Parent, Parent's parent, and so on until root.
     */
    public function parentRecursive(): BelongsTo
    {
        return $this->parent()->with('parentRecursive');
    }

    /**
     * Get current model's all recursive parents in a collection in flat structure.
     */
    public function parentRecursiveFlatten()
    {
        $result = collect();
        $item = $this->parentRecursive;
        if ($item instanceof User) {
            $result->push($item);
            $result = $result->merge($item->parentRecursiveFlatten());
        }

        return $result;
    }

    /**
     * This will give model's Children
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * This will give model's Children, Children's Children and so on until last node.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Get all the selected text inside this code for a specific source.
     */
    public function selectionsForSource($sourceId): HasMany
    {
        return $this->selections()->where('source_id', $sourceId);
    }

    /**
     * Add a nested representation of the code to the array.
     */
    public function toArrayWithNesting(): array
    {
        $nestedCode = [
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'codebook' => $this->codebook->id,
            'description' => $this->description ?? '',
            'children' => [],
        ];

        foreach ($this->children as $child) {
            $nestedCode['children'][] = $child->toArrayWithNesting();
        }

        return $nestedCode;
    }

    /**
     * Remove the parent of a code
     */
    public function removeParent(): void
    {
        $this->parent_id = null;
        $this->save();
    }

    /**
     * Move the code up in the hierarchy by one level
     * there's no control over the parent_id because the call is made only from a child code
     */
    public function moveUpHierarchy(): void
    {
        $this->parent_id = $this->parent->parent_id;
        $this->save();
    }
}
