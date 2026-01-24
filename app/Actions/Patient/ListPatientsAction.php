<?php

namespace App\Actions\Patient;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class ListPatientsAction
{
    public function __invoke(): JsonResponse
    {
        $perPage = request('per_page', 15);
        $patients = Patient::with(['nationality', 'occupation', 'maritalStatus'])
            ->orderBy('created_at', 'desc')
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
