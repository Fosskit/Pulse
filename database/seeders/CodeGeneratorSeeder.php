<?php

namespace Database\Seeders;

use App\Models\CodeGenerator;
use Illuminate\Database\Seeder;

class CodeGeneratorSeeder extends Seeder
{
    public function run(): void
    {
        CodeGenerator::create([
            'entity' => 'patient',
            'prefix' => 'PAT',
            'format' => '{prefix}-{year}-{seq:5}',
            'current_sequence' => 0,
            'reset_yearly' => true,
            'reset_monthly' => false,
            'padding' => 5,
        ]);
    }
}
