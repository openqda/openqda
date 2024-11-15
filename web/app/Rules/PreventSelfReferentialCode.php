<?php

namespace App\Rules;

use App\Models\Code;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PreventSelfReferentialCode implements ValidationRule
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
        if ($value === null) {
            return;
        }

        // For new codes (no ID yet), just verify parent exists
        if (! $this->codeId) {
            if (! Code::where('id', $value)->exists()) {
                $fail('The selected parent code does not exist.');
            }

            return;
        }

        // For existing codes, check that parent_id isn't the same as the code's ID
        if ($value === $this->codeId) {
            $fail('A code cannot be its own parent.');
        }
    }
}
