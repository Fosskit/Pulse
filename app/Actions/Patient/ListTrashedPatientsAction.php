<?php

namespace App\Actions\Patient;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class ListTrashedPatientsAction
{
    public function __invoke(): JsonResponse
    {
        $perPage = request('per_page', 15);
        
        // Build query for trashed patients
        $query = Patient::onlyTrashed()
            ->with(['nationality', 'occupation', 'maritalStatus', 'status']);
        
        // Apply status filter if provided
        if (request()->has('status_id') && request('status_id') !== null) {
            $query->where('status_id', request('status_id'));
        }
        
        // Apply search filter if provided
        if (request()->has('search') && request('search') !== null) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(surname, ' ', name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$search}%"]);
            });
        }
        
        $patients = $query->orderBy('deleted_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => PatientResource::collection($patients->items()),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ],
        ]);
    }
}
