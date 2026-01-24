<?php

namespace App\Services;

use App\Models\CodeGenerator;

class CodeGeneratorService
{
    /**
     * Generate a code for the given entity
     */
    public function generate(string $entity): string
    {
        $generator = CodeGenerator::where('entity', $entity)->first();

        if (!$generator) {
            throw new \Exception("Code generator not configured for entity: {$entity}");
        }

        return $generator->generateCode();
    }

    /**
     * Get or create a code generator for an entity
     */
    public function getOrCreate(string $entity, array $defaults = []): CodeGenerator
    {
        return CodeGenerator::firstOrCreate(
            ['entity' => $entity],
            array_merge([
                'prefix' => strtoupper(substr($entity, 0, 3)),
                'format' => '{prefix}-{year}-{seq:5}',
                'padding' => 5,
                'reset_yearly' => true,
                'reset_monthly' => false,
            ], $defaults)
        );
    }
}
