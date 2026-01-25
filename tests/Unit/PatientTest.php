<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\Reference\PatientStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed patient statuses
        $this->artisan('db:seed', ['--class' => 'PatientStatusSeeder']);
    }

    #[Test]
    public function it_can_filter_patients_by_status()
    {
        // Create patients with different statuses
        $activePatient = Patient::factory()->create(['status_id' => 1]); // Active
        $inactivePatient = Patient::factory()->create(['status_id' => 2]); // Inactive
        $archivedPatient = Patient::factory()->create(['status_id' => 3]); // Archived

        // Test filtering by Active status
        $activePatients = Patient::withStatus(1)->get();
        $this->assertCount(1, $activePatients);
        $this->assertEquals($activePatient->id, $activePatients->first()->id);

        // Test filtering by Inactive status
        $inactivePatients = Patient::withStatus(2)->get();
        $this->assertCount(1, $inactivePatients);
        $this->assertEquals($inactivePatient->id, $inactivePatients->first()->id);

        // Test filtering by Archived status
        $archivedPatients = Patient::withStatus(3)->get();
        $this->assertCount(1, $archivedPatients);
        $this->assertEquals($archivedPatient->id, $archivedPatients->first()->id);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_patients_match_status()
    {
        // Create only active patients
        Patient::factory()->count(3)->create(['status_id' => 1]);

        // Try to filter by Blocked status (id: 5)
        $blockedPatients = Patient::withStatus(5)->get();
        
        $this->assertCount(0, $blockedPatients);
    }

    #[Test]
    public function it_can_chain_with_status_scope_with_other_queries()
    {
        // Create patients with different statuses and names
        $activeJohn = Patient::factory()->create([
            'status_id' => 1,
            'name' => 'John',
        ]);
        
        $activeJane = Patient::factory()->create([
            'status_id' => 1,
            'name' => 'Jane',
        ]);
        
        $inactiveJohn = Patient::factory()->create([
            'status_id' => 2,
            'name' => 'John',
        ]);

        // Filter by status and name
        $activeJohns = Patient::withStatus(1)
            ->where('name', 'John')
            ->get();

        $this->assertCount(1, $activeJohns);
        $this->assertEquals($activeJohn->id, $activeJohns->first()->id);
    }
}
