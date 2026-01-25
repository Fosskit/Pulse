<?php

namespace App\Actions\Patient;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class RestorePatientAction
{
    public function __invoke(int $id): JsonResponse
    {
        // Find the trashed patient
        $patient = Patient::onlyTrashed()->findOrFail($id);
        
        // Restore the patient first (this will trigger the 'restored' event for activity logging)
        $patient->restore();
        
        // Then clear the deleted_by field
        $patient->deleted_by = null;
        $patient->save();
        
        // Load relationships
        $patient->load(['nationality', 'occupation', 'maritalStatus', 'status']);

        return response()->json([
            'data' => new PatientResource($patient),
            'message' => 'Patient restored successfully',
        ]);
    }
}
