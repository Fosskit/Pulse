<?php

namespace App\Actions\Patient;

use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class StorePatientAction
{
    public function __invoke(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());
        $patient->load(['nationality', 'occupation', 'maritalStatus']);

        return response()->json([
            'data' => new PatientResource($patient),
            'message' => 'Patient created successfully',
        ], 201);
    }
}
