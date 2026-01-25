<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListTrashedPatientsActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed patient statuses
        $this->artisan('db:seed', ['--class' => 'PatientStatusSeeder']);
        
        // Create and authenticate a user
        $user = User::factory()->create();
        Passport::actingAs($user);
    }

    #[Test]
    public function it_returns_only_trashed_patients()
    {
        // Create active patients
        Patient::factory()->count(3)->create();
        
        // Create trashed patients
        $trashedPatients = Patient::factory()->count(2)->create();
        foreach ($trashedPatients as $patient) {
            $patient->delete();
        }

        $response = $this->getJson('/api/patients/trash');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'surname',
                    'name',
                    'deleted_at',
                    'deleted_by',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    #[Test]
    public function it_includes_relationships_in_response()
    {
        // Create and trash a patient with relationships
        $patient = Patient::factory()->create([
            'nationality_id' => 1,
            'status_id' => 1,
        ]);
        $patient->delete();

        $response = $this->getJson('/api/patients/trash');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'nationality',
                    'marital_status',
                    'occupation',
                    'status',
                ],
            ],
        ]);
    }

    #[Test]
    public function it_filters_trashed_patients_by_status()
    {
        // Create trashed patients with different statuses
        $activePatient = Patient::factory()->create(['status_id' => 1]);
        $activePatient->delete();
        
        $inactivePatient = Patient::factory()->create(['status_id' => 2]);
        $inactivePatient->delete();

        // Filter by active status
        $response = $this->getJson('/api/patients/trash?status_id=1');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($activePatient->id, $response->json('data.0.id'));
    }

    #[Test]
    public function it_paginates_trashed_patients()
    {
        // Create 20 trashed patients
        $patients = Patient::factory()->count(20)->create();
        foreach ($patients as $patient) {
            $patient->delete();
        }

        // Request first page with 10 per page
        $response = $this->getJson('/api/patients/trash?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJson([
            'meta' => [
                'current_page' => 1,
                'last_page' => 2,
                'per_page' => 10,
                'total' => 20,
            ],
        ]);
    }

    #[Test]
    public function it_orders_trashed_patients_by_deleted_at_desc()
    {
        // Create patients and delete them at different times
        $firstDeleted = Patient::factory()->create();
        $firstDeleted->delete();
        
        sleep(1); // Ensure different timestamps
        
        $secondDeleted = Patient::factory()->create();
        $secondDeleted->delete();

        $response = $this->getJson('/api/patients/trash');

        $response->assertStatus(200);
        // Most recently deleted should be first
        $this->assertEquals($secondDeleted->id, $response->json('data.0.id'));
        $this->assertEquals($firstDeleted->id, $response->json('data.1.id'));
    }
}
