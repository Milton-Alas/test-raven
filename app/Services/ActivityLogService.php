<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function log(
        ?Model $causer,
        ?Model $subject,
        string $event,
        ?string $description = null,
        array $properties = [],
        array $changes = []
    ): void {
        ActivityLog::create([
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer?->id,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
            'changes' => $changes,
        ]);
    }
}
