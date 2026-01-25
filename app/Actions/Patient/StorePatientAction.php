<?php

namespace App\Actions\Patient;

use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\CodeGeneratorService;
use Illuminate\Http\JsonResponse;

class StorePatientAction
{
    public function __invoke(StorePatientRequest $request, CodeGeneratorService $codeGenerator): JsonResponse
    {
        $data = $request->validated();
        
        // Auto-generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = $codeGenerator->generate('patient');
        }
        
        // Set audit trail and default status
        $data['created_by'] = auth()->id();
        $data['status_id'] ??= 1; // Default to Active
        
        $patient = Patient::create($data);
        $patient->load(['nationality', 'occupation', 'maritalStatus']);

        return response()->json([
            'data' => new PatientResource($patient),
            'message' => 'Patient created successfully',
        ], 201);
    }
}
