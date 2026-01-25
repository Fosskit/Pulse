<?php

namespace App\Actions\Patient;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class DeletePatientAction
{
    public function __invoke(Patient $patient): JsonResponse
    {
        // Set deleted_by before soft delete for audit trail
        $patient->deleted_by = auth()->id();
        $patient->save();
        
        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully',
        ]);
    }
}
