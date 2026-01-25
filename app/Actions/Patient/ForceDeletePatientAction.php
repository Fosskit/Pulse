<?php

namespace App\Actions\Patient;

use App\Models\Patient;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForceDeletePatientAction
{
    public function __invoke(int $id, ActivityLogService $activityLogService, Request $request): JsonResponse
    {
        // Find the trashed patient (only allow force delete on already soft-deleted patients)
        $patient = Patient::onlyTrashed()->findOrFail($id);
        
        // Store patient info for logging before deletion
        $patientCode = $patient->code;
        $patientName = $patient->name;
        
        // Log the force delete action
        $activityLogService->log(
            action: 'force_deleted',
            model: 'Patient',
            modelId: $id,
            description: "Patient permanently deleted: {$patientCode} - {$patientName}",
            request: $request
        );
        
        // Permanently delete the patient
        $patient->forceDelete();

        return response()->json([
            'message' => 'Patient permanently deleted',
        ]);
    }
}
