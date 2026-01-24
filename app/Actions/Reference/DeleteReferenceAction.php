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
        $item->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
