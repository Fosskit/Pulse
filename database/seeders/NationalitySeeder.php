<?php

namespace Database\Seeders;

use App\Models\Reference\Nationality;
use Illuminate\Database\Seeder;

class NationalitySeeder extends Seeder
{
    public function run(): void
    {
        $nationalities = [
            ['code' => 'KH', 'name' => 'Cambodian', 'description' => 'Kingdom of Cambodia'],
            ['code' => 'TH', 'name' => 'Thai', 'description' => 'Kingdom of Thailand'],
            ['code' => 'VN', 'name' => 'Vietnamese', 'description' => 'Socialist Republic of Vietnam'],
            ['code' => 'LA', 'name' => 'Lao', 'description' => 'Lao People\'s Democratic Republic'],
            ['code' => 'MM', 'name' => 'Myanmar', 'description' => 'Republic of the Union of Myanmar'],
            ['code' => 'CN', 'name' => 'Chinese', 'description' => 'People\'s Republic of China'],
            ['code' => 'US', 'name' => 'American', 'description' => 'United States of America'],
            ['code' => 'FR', 'name' => 'French', 'description' => 'French Republic'],
            ['code' => 'AU', 'name' => 'Australian', 'description' => 'Commonwealth of Australia'],
            ['code' => 'JP', 'name' => 'Japanese', 'description' => 'Japan'],
            ['code' => 'KR', 'name' => 'Korean', 'description' => 'Republic of Korea'],
            ['code' => 'SG', 'name' => 'Singaporean', 'description' => 'Republic of Singapore'],
            ['code' => 'MY', 'name' => 'Malaysian', 'description' => 'Malaysia'],
            ['code' => 'ID', 'name' => 'Indonesian', 'description' => 'Republic of Indonesia'],
            ['code' => 'PH', 'name' => 'Filipino', 'description' => 'Republic of the Philippines'],
        ];

        foreach ($nationalities as $nationality) {
            Nationality::create([
                'code' => $nationality['code'],
                'name' => $nationality['name'],
                'description' => $nationality['description'],
                'status_id' => 1,
            ]);
        }
    }
}
