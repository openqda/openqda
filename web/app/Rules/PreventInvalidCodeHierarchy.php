<?php

namespace App\Rules;

use App\Models\Code;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PreventInvalidCodeHierarchy implements ValidationRule
{
    protected ?string $codeId;

    public function __construct(?string $codeId = null)
    {
        $this->codeId = $codeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If no parent_id is provided (null), it's valid
        // because we then make the code a top-level code
        if ($value === null) {
            return;
        }

        // otherwise we always validate, if the parent
        // actually exists by given ID
        if (! Code::where('id', $value)->exists()) {
            $fail('The selected parent code does not exist.');
        }

        // For existing codes, check that parent_id isn't the same as the code's ID
        if ($value === $this->codeId) {
            $fail('A code cannot be its own parent.');
        }

        // For new codes (no ID yet), just verify parent exists
        if (! $this->codeId) {
            return;
        }

        // for existing codes, do a few more checks:
        // get the current code
        $code = Code::find($this->codeId);

        // if parent is already the code's actual parent
        if ($value == $code->parent_id) {
            $fail(`Cannot assign already assigned parent to code.`);
        }

        // pass, if no there a re no children to check
        if (! $this->hasChildren($code)) {
            return;
        }

        // most expensive: recursively check if the new parent
        // is actually a child of the code
        if ($this->lookupChildrenForParentId($code, $value)) {
            $fail('Cannot make this code\'s child to become a parent.');
        }

        // otherwise we should be fine!
    }

    protected function lookupChildrenForParentId($code, $parentId)
    {
        if ($code->id == $parentId) {
            return true;
        }
        if (! $this->hasChildren($code)) {
            return false;
        }

        foreach ($code->children as $child) {
            if ($this->lookupChildrenForParentId($child, $parentId)) {
                return true;
            }
        }

        return false;
    }

    protected function hasChildren($code)
    {
        return $code->children && count($code->children) > 0;
    }
}
