<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(
        string $action,
        ?string $model = null,
        ?int $modelId = null,
        ?string $description = null,
        ?Request $request = null
    ): ?ActivityLog {
        // Check global and per-model logging config
        $enabled = config('activitylog.enabled', true);
        $modelLogging = config('activitylog.model_logging', []);
        if (!$enabled) {
            return null;
        }
        if ($model && isset($modelLogging[strtolower($model)]) && !$modelLogging[strtolower($model)]) {
            return null;
        }

        $userId = auth()->id();
        $ipAddress = $request?->ip();
        $userAgent = $request?->userAgent();

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
