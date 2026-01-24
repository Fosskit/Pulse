<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_generators', function (Blueprint $table) {
            $table->id();
            $table->string('entity')->unique(); // e.g., 'patient', 'invoice'
            $table->string('prefix')->nullable(); // e.g., 'PAT', 'INV'
            $table->string('format'); // e.g., '{prefix}-{year}-{seq:5}', '{prefix}{seq:6}'
            $table->unsignedInteger('current_sequence')->default(0);
            $table->boolean('reset_yearly')->default(false);
            $table->boolean('reset_monthly')->default(false);
            $table->unsignedInteger('padding')->default(5); // Number of digits for sequence
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_generators');
    }
};
