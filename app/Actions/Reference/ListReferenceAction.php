<?php

namespace App\Actions\Reference;

use App\Http\Resources\ReferenceResource;
use Illuminate\Database\Eloquent\Model;

class ListReferenceAction
{
    public function execute(string $modelClass, array $filters = [])
    {
        $query = $modelClass::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return ReferenceResource::collection(
            $query->orderBy('name')->paginate($perPage)
        );
    }
}
