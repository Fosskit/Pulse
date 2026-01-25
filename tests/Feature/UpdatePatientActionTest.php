<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Reference\Nationality;
use App\Models\Reference\Occupation;
use App\Models\Reference\MaritalStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePatientActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations on logging connection for tests
        $this->artisan('migrate', ['--database' => 'logging']);
        
        // Seed required reference data
        $this->artisan('db:seed', ['--class' => 'PatientStatusSeeder']);
        $this->artisan('db:seed', ['--class' => 'NationalitySeeder']);
        $this->artisan('db:seed', ['--class' => 'OccupationSeeder']);
        $this->artisan('db:seed', ['--class' => 'MaritalStatusSeeder']);
    }

    #[Test]
    public function it_sets_updated_by_to_authenticated_user()
    {
        // Create users in the main database first
        $creator = User::factory()->create();
        $updater = User::factory()->create();
        
        // Create patient as first user
        Passport::actingAs($creator);
        $patient = Patient::factory()->create([
            'created_by' => $creator->id,
        ]);

        // Update patient as second user
        Passport::actingAs($updater);

        $nationality = Nationality::first();
        $occupation = Occupation::first();
        $maritalStatus = MaritalStatus::first();

        $response = $this->putJson("/api/patients/{$patient->id}", [
            'code' => $patient->code,
            'name' => 'Updated Name',
            'birthdate' => $patient->birthdate->format('Y-m-d'),
            'sex' => $patient->sex,
            'nationality_id' => $nationality->id,
            'occupation_id' => $occupation->id,
            'marital_status_id' => $maritalStatus->id,
            'telephone' => $patient->telephone,
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'Updated Name',
            'created_by' => $creator->id,
            'updated_by' => $updater->id,
        ]);
    }

    #[Test]
    public function it_updates_patient_successfully()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $patient = Patient::factory()->create();
        $nationality = Nationality::first();
        $occupation = Occupation::first();
        $maritalStatus = MaritalStatus::first();

        $response = $this->putJson("/api/patients/{$patient->id}", [
            'code' => $patient->code,
            'name' => 'New Patient Name',
            'birthdate' => '1995-06-15',
            'sex' => 'F',
            'nationality_id' => $nationality->id,
            'occupation_id' => $occupation->id,
            'marital_status_id' => $maritalStatus->id,
            'telephone' => '9999999999',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'name',
                'birthdate',
                'sex',
            ],
            'message',
        ]);

        $patient->refresh();
        $this->assertEquals('New Patient Name', $patient->name);
        $this->assertEquals('1995-06-15', $patient->birthdate->format('Y-m-d'));
        $this->assertEquals('F', $patient->sex);
        $this->assertEquals('9999999999', $patient->telephone);
    }
}
