<?php

namespace App\Actions\Settings\CodeGenerator;

use App\Models\CodeGenerator;
use Illuminate\Http\JsonResponse;

class ListCodeGeneratorsAction
{
    public function __invoke(): JsonResponse
    {
        $generators = CodeGenerator::orderBy('entity')->get();

        return response()->json([
            'data' => $generators,
        ]);
    }
}
