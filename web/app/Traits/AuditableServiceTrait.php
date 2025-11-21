<?php

namespace App\Traits;

use OwenIt\Auditing\Models\Audit;

trait AuditableServiceTrait
{
    use \OwenIt\Auditing\Auditable;

    public function createAudit(string $event, array $newValues = []): void
    {
        $audit = new Audit([
            'user_type' => 'App\Models\User',
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_id' => $this->id ?? -1,
            'auditable_type' => get_class($this),
            'new_values' => $newValues,
        ]);

        $audit->save();
    }
}
