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
    ): ActivityLog {
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
