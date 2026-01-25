<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Reference\Nationality;
use App\Models\Reference\Occupation;
use App\Models\Reference\MaritalStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorePatientActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required reference data
        $this->artisan('db:seed', ['--class' => 'PatientStatusSeeder']);
        $this->artisan('db:seed', ['--class' => 'NationalitySeeder']);
        $this->artisan('db:seed', ['--class' => 'OccupationSeeder']);
        $this->artisan('db:seed', ['--class' => 'MaritalStatusSeeder']);
    }

    #[Test]
    public function it_sets_created_by_to_authenticated_user()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $nationality = Nationality::first();
        $occupation = Occupation::first();
        $maritalStatus = MaritalStatus::first();

        $response = $this->postJson('/api/patients', [
            'code' => 'PAT-001',
            'name' => 'John Doe',
            'birthdate' => '1990-01-01',
            'sex' => 'M',
            'nationality_id' => $nationality->id,
            'occupation_id' => $occupation->id,
            'marital_status_id' => $maritalStatus->id,
            'telephone' => '1234567890',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('patients', [
            'name' => 'John Doe',
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function it_defaults_status_id_to_active_when_not_provided()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $nationality = Nationality::first();
        $occupation = Occupation::first();
        $maritalStatus = MaritalStatus::first();

        $response = $this->postJson('/api/patients', [
            'code' => 'PAT-002',
            'name' => 'Jane Doe',
            'birthdate' => '1992-05-15',
            'sex' => 'F',
            'nationality_id' => $nationality->id,
            'occupation_id' => $occupation->id,
            'marital_status_id' => $maritalStatus->id,
            'telephone' => '0987654321',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('patients', [
            'name' => 'Jane Doe',
            'status_id' => 1, // Active status
        ]);
    }

    #[Test]
    public function it_respects_provided_status_id()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $nationality = Nationality::first();
        $occupation = Occupation::first();
        $maritalStatus = MaritalStatus::first();

        $response = $this->postJson('/api/patients', [
            'code' => 'PAT-003',
            'name' => 'Bob Smith',
            'birthdate' => '1985-12-20',
            'sex' => 'M',
            'nationality_id' => $nationality->id,
            'occupation_id' => $occupation->id,
            'marital_status_id' => $maritalStatus->id,
            'telephone' => '5555555555',
            'status_id' => 2, // Inactive status
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('patients', [
            'name' => 'Bob Smith',
            'status_id' => 2, // Should use provided status
        ]);
    }
}
