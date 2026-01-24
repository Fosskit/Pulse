<?php

namespace App\Actions\Reference;

use App\Http\Resources\ReferenceResource;
use App\Services\ActivityLogService;

class StoreReferenceAction
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function execute(string $modelClass, array $data)
    {
        $item = $modelClass::create($data);
        return new ReferenceResource($item);
    }
}
