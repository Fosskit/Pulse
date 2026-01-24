<?php

namespace App\Actions\Reference;

use App\Http\Resources\ReferenceResource;
use App\Services\ActivityLogService;

class UpdateReferenceAction
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function execute(string $modelClass, int $id, array $data)
    {
        $item = $modelClass::findOrFail($id);
        $item->update($data);
        return new ReferenceResource($item);
    }
}
