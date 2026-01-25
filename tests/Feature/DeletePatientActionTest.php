<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DeletePatientActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable activity logging for tests
        Config::set('activitylog.enabled', false);
    }

    /** @test */
    public function it_soft_deletes_a_patient_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $patient = Patient::factory()->create();

        // Act
        $response = $this->deleteJson("/api/patients/{$patient->id}");

        // Assert
        $response->assertOk();
        $response->assertJson([
            'message' => 'Patient deleted successfully',
        ]);

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    /** @test */
    public function it_sets_deleted_by_field_when_soft_deleting()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $patient = Patient::factory()->create();

        // Act
        $this->deleteJson("/api/patients/{$patient->id}");

        // Assert
        $patient->refresh();
        $this->assertEquals($user->id, $patient->deleted_by);
        $this->assertNotNull($patient->deleted_at);
    }

    /** @test */
    public function it_requires_authentication_to_delete_patient()
    {
        // Arrange
        $patient = Patient::factory()->create();

        // Act
        $response = $this->deleteJson("/api/patients/{$patient->id}");

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_returns_404_for_non_existent_patient()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        // Act
        $response = $this->deleteJson('/api/patients/99999');

        // Assert
        $response->assertNotFound();
    }
}
