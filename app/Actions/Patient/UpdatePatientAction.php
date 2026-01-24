<?php

namespace App\Actions\Patient;

use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class UpdatePatientAction
{
    public function __invoke(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());
        $patient->load(['nationality', 'occupation', 'maritalStatus']);

        return response()->json([
            'data' => new PatientResource($patient),
            'message' => 'Patient updated successfully',
        ]);
    }
}
