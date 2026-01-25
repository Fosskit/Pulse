<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Reference\Nationality;
use App\Models\Reference\PatientStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RestorePatientActionTest extends TestCase
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
    public function it_can_restore_a_soft_deleted_patient()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create([
            'deleted_by' => $user->id,
        ]);
        $patient->delete(); // Soft delete
        
        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
        
        // Act
        $response = $this->postJson("/api/patients/{$patient->id}/restore");
        
        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Patient restored successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'code',
                    'surname',
                    'name',
                ],
            ]);
        
        // Verify patient is no longer soft deleted
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'deleted_at' => null,
            'deleted_by' => null,
        ]);
    }

    /** @test */
    public function it_clears_deleted_by_field_when_restoring()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create([
            'deleted_by' => $user->id,
        ]);
        $patient->delete();
        
        // Act
        $this->postJson("/api/patients/{$patient->id}/restore");
        
        // Assert
        $patient->refresh();
        $this->assertNull($patient->deleted_by);
        $this->assertNull($patient->deleted_at);
    }

    /** @test */
    public function it_logs_restore_action_to_activity_log()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create();
        $patient->delete();
        
        // Clear any existing activity logs to isolate the restore log
        \App\Models\ActivityLog::query()->delete();
        
        // Act
        $this->postJson("/api/patients/{$patient->id}/restore");
        
        // Assert - check if the restored log was created
        $restoredLog = \App\Models\ActivityLog::where('action', 'patient.restored')
            ->where('model', 'Patient')
            ->where('model_id', $patient->id)
            ->first();
        
        $this->assertNotNull($restoredLog, 'Restored activity log should exist');
        $this->assertEquals($user->id, $restoredLog->user_id);
    }

    /** @test */
    public function it_returns_404_for_non_trashed_patient()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $patient = Patient::factory()->create(); // Not deleted
        
        // Act
        $response = $this->postJson("/api/patients/{$patient->id}/restore");
        
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
        $response = $this->postJson('/api/patients/99999/restore');
        
        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_loads_patient_relationships_after_restore()
    {
        // Arrange
        $user = User::factory()->create();
        Passport::actingAs($user);
        
        $nationality = Nationality::first();
        $patient = Patient::factory()->create([
            'nationality_id' => $nationality->id,
        ]);
        $patient->delete();
        
        // Act
        $response = $this->postJson("/api/patients/{$patient->id}/restore");
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'nationality',
                    'occupation',
                    'marital_status',
                    'status',
                ],
            ]);
    }
}
