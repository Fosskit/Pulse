<?php

namespace App\Actions\Patient;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class ShowPatientAction
{
    public function __invoke(Patient $patient): JsonResponse
    {
        $patient->load(['nationality', 'occupation', 'maritalStatus']);

        return response()->json([
            'data' => new PatientResource($patient),
        ]);
    }
}
