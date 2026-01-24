<?php

namespace App\Actions\Reference;

use App\Http\Resources\ReferenceResource;

class ShowReferenceAction
{
    public function execute(string $modelClass, int $id)
    {
        $item = $modelClass::findOrFail($id);
        return new ReferenceResource($item);
    }
}
