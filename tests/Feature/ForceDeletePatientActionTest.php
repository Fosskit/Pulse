<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Reference\Nationality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ForceDeletePatientActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed necessary reference data
        $this->seed(\Database\Seeders\NationalitySeeder::class);
        $this->seed(\Database\Seeders\PatientStatusSeeder::class);
    }

    /** @test */
    public function it_can_permanently_delete_a_soft_deleted_patient()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create();
        $patient->delete(); // Soft delete first
        
        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        
        // Act
        $response = $this->deleteJson("/api/patients/{$patient->id}/force");
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Patient permanently deleted',
            ]);
        
        // Verify patient is completely removed from database
        $this->assertDatabaseMissing('patients', [
            'id' => $patient->id,
        ]);
    }

    /** @test */
    public function it_logs_force_delete_action_to_activity_log()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create([
            'code' => 'PAT-001',
            'name' => 'John',
        ]);
        $patientId = $patient->id;
        $patient->delete();
        
        // Clear any existing activity logs to isolate the force delete log
        \App\Models\ActivityLog::query()->delete();
        
        // Act
        $this->deleteJson("/api/patients/{$patientId}/force");
        
        // Assert - check if the force delete log was created
        $forceDeleteLog = \App\Models\ActivityLog::where('action', 'force_deleted')
            ->where('model', 'Patient')
            ->where('model_id', $patientId)
            ->first();
        
        $this->assertNotNull($forceDeleteLog, 'Force delete activity log should exist');
        $this->assertEquals($user->id, $forceDeleteLog->user_id);
        $this->assertStringContainsString('PAT-001', $forceDeleteLog->description);
        $this->assertStringContainsString('John', $forceDeleteLog->description);
    }

    /** @test */
    public function it_returns_404_for_non_trashed_patient()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create(); // Not deleted
        
        // Act
        $response = $this->deleteJson("/api/patients/{$patient->id}/force");
        
        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_for_non_existent_patient()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        // Act
        $response = $this->deleteJson('/api/patients/99999/force');
        
        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_only_allows_force_delete_on_already_soft_deleted_patients()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create();
        // Don't soft delete - try to force delete directly
        
        // Act
        $response = $this->deleteJson("/api/patients/{$patient->id}/force");
        
        // Assert
        $response->assertStatus(404); // Should not find the patient in trash
        
        // Verify patient still exists (not force deleted)
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
        ]);
    }
}
