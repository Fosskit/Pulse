<?php

namespace App\Actions\Patient;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class DeletePatientAction
{
    public function __invoke(Patient $patient): JsonResponse
    {
        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully',
        ]);
    }
}
