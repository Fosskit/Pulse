<?php

namespace App\Actions\Reference;

use App\Services\ActivityLogService;

class DeleteReferenceAction
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function execute(string $modelClass, int $id)
    {
        $item = $modelClass::findOrFail($id);
        $name = $item->name;
        
        $item->delete();

        $this->activityLogService->log(
            action: 'deleted',
            model: $item,
            description: "Deleted {$name}"
        );

        return response()->json(['message' => 'Deleted successfully']);
    }
}
