<?php

namespace Database\Seeders;

use App\Models\Reference\PatientStatus;
use Illuminate\Database\Seeder;

class PatientStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'id' => 1,
                'code' => 'active',
                'name' => 'Active',
                'description' => 'Default status for new patients who are actively receiving care',
                'color' => 'green',
            ],
            [
                'id' => 2,
                'code' => 'inactive',
                'name' => 'Inactive',
                'description' => 'Patient no longer visits the facility',
                'color' => 'gray',
            ],
            [
                'id' => 3,
                'code' => 'archived',
                'name' => 'Archived',
                'description' => 'Old records kept for historical purposes, read-only',
                'color' => 'blue',
            ],
            [
                'id' => 4,
                'code' => 'pending',
                'name' => 'Pending Verification',
                'description' => 'New patient registrations awaiting verification',
                'color' => 'yellow',
            ],
            [
                'id' => 5,
                'code' => 'blocked',
                'name' => 'Blocked',
                'description' => 'Patient record flagged for review or restricted access',
                'color' => 'red',
            ],
        ];

        foreach ($statuses as $status) {
            PatientStatus::create([
                'id' => $status['id'],
                'code' => $status['code'],
                'name' => $status['name'],
                'description' => $status['description'],
                'color' => $status['color'],
                'status_id' => 1, // All patient statuses are active by default
            ]);
        }
    }
}
