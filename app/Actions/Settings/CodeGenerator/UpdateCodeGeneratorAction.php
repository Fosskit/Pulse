<?php

namespace App\Actions\Settings\CodeGenerator;

use App\Http\Requests\Settings\UpdateCodeGeneratorRequest;
use App\Models\CodeGenerator;
use Illuminate\Http\JsonResponse;

class UpdateCodeGeneratorAction
{
    public function __invoke(UpdateCodeGeneratorRequest $request, CodeGenerator $codeGenerator): JsonResponse
    {
        $codeGenerator->update($request->validated());

        return response()->json([
            'data' => $codeGenerator,
            'message' => 'Code generator updated successfully',
        ]);
    }
}
