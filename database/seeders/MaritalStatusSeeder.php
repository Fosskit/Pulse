<?php

namespace Database\Seeders;

use App\Models\Reference\MaritalStatus;
use Illuminate\Database\Seeder;

class MaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'SINGLE',
                'name' => 'Single',
                'description' => 'Never married',
            ],
            [
                'code' => 'MARRIED',
                'name' => 'Married',
                'description' => 'Currently married',
            ],
            [
                'code' => 'DIVORCED',
                'name' => 'Divorced',
                'description' => 'Legally divorced',
            ],
            [
                'code' => 'WIDOWED',
                'name' => 'Widowed',
                'description' => 'Spouse deceased',
            ],
            [
                'code' => 'SEPARATED',
                'name' => 'Separated',
                'description' => 'Separated but not divorced',
            ],
            [
                'code' => 'COHABIT',
                'name' => 'Cohabiting',
                'description' => 'Living together without marriage',
            ],
        ];

        foreach ($statuses as $status) {
            MaritalStatus::create([
                'code' => $status['code'],
                'name' => $status['name'],
                'description' => $status['description'],
                'status_id' => 1,
            ]);
        }
    }
}
