<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->index()->nullable();
            $table->string('surname', 191)->nullable();
            $table->string('name', 191)->nullable();
            $table->string('telephone', 191)->nullable();
            $table->enum('sex', ['M', 'F', 'O', 'U'])->default('U');
            $table->date('birthdate')->nullable();
            $table->boolean('multiple_birth')->default(false)->nullable()->index();
            $table->unsignedTinyInteger('nationality_id')->default(1)->index();
            $table->unsignedSmallInteger('marital_status_id')->index()->nullable();
            $table->unsignedSmallInteger('occupation_id')->index()->nullable();
            $table->boolean('deceased')->nullable();
            $table->date('deceased_at')->nullable();
            $table->metaFields();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
