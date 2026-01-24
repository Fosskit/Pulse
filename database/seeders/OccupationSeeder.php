<?php

namespace Database\Seeders;

use App\Models\Reference\Occupation;
use Illuminate\Database\Seeder;

class OccupationSeeder extends Seeder
{
    public function run(): void
    {
        $occupations = [
            // Agriculture & Farming
            ['code' => 'FARM', 'name' => 'Farmer', 'description' => 'Agricultural worker, rice farmer'],
            ['code' => 'FISH', 'name' => 'Fisherman', 'description' => 'Fishing industry worker'],
            
            // Business & Commerce
            ['code' => 'MERCH', 'name' => 'Merchant', 'description' => 'Shop owner, trader'],
            ['code' => 'VEND', 'name' => 'Street Vendor', 'description' => 'Market or street vendor'],
            ['code' => 'ENTR', 'name' => 'Entrepreneur', 'description' => 'Business owner'],
            
            // Construction & Labor
            ['code' => 'CONST', 'name' => 'Construction Worker', 'description' => 'Building and construction'],
            ['code' => 'CARP', 'name' => 'Carpenter', 'description' => 'Woodworking professional'],
            ['code' => 'ELEC', 'name' => 'Electrician', 'description' => 'Electrical work'],
            ['code' => 'PLUMB', 'name' => 'Plumber', 'description' => 'Plumbing services'],
            ['code' => 'MECH', 'name' => 'Mechanic', 'description' => 'Vehicle or machinery repair'],
            
            // Education
            ['code' => 'TEACH', 'name' => 'Teacher', 'description' => 'Education professional'],
            ['code' => 'PROF', 'name' => 'Professor', 'description' => 'University lecturer'],
            ['code' => 'TUTOR', 'name' => 'Tutor', 'description' => 'Private tutor'],
            
            // Healthcare
            ['code' => 'DOC', 'name' => 'Doctor', 'description' => 'Medical doctor'],
            ['code' => 'NURSE', 'name' => 'Nurse', 'description' => 'Nursing professional'],
            ['code' => 'PHARM', 'name' => 'Pharmacist', 'description' => 'Pharmacy professional'],
            ['code' => 'DENT', 'name' => 'Dentist', 'description' => 'Dental professional'],
            ['code' => 'MIDW', 'name' => 'Midwife', 'description' => 'Traditional or professional midwife'],
            
            // Government & Public Service
            ['code' => 'CIVIL', 'name' => 'Civil Servant', 'description' => 'Government employee'],
            ['code' => 'POLICE', 'name' => 'Police Officer', 'description' => 'Law enforcement'],
            ['code' => 'MILIT', 'name' => 'Military', 'description' => 'Armed forces member'],
            
            // Transportation
            ['code' => 'DRIVER', 'name' => 'Driver', 'description' => 'Vehicle driver'],
            ['code' => 'MOTO', 'name' => 'Moto-taxi Driver', 'description' => 'Motorcycle taxi driver'],
            ['code' => 'TUK', 'name' => 'Tuk-tuk Driver', 'description' => 'Tuk-tuk operator'],
            
            // Hospitality & Tourism
            ['code' => 'HOTEL', 'name' => 'Hotel Staff', 'description' => 'Hotel or guesthouse worker'],
            ['code' => 'GUIDE', 'name' => 'Tour Guide', 'description' => 'Tourism guide'],
            ['code' => 'CHEF', 'name' => 'Chef/Cook', 'description' => 'Culinary professional'],
            ['code' => 'WAIT', 'name' => 'Waiter/Waitress', 'description' => 'Restaurant service'],
            
            // Manufacturing & Factory
            ['code' => 'GARM', 'name' => 'Garment Worker', 'description' => 'Textile and garment industry'],
            ['code' => 'FACT', 'name' => 'Factory Worker', 'description' => 'Manufacturing worker'],
            
            // Professional Services
            ['code' => 'ENG', 'name' => 'Engineer', 'description' => 'Engineering professional'],
            ['code' => 'ARCH', 'name' => 'Architect', 'description' => 'Architecture professional'],
            ['code' => 'ACC', 'name' => 'Accountant', 'description' => 'Accounting professional'],
            ['code' => 'LAW', 'name' => 'Lawyer', 'description' => 'Legal professional'],
            ['code' => 'IT', 'name' => 'IT Professional', 'description' => 'Information technology'],
            
            // Arts & Culture
            ['code' => 'ART', 'name' => 'Artist', 'description' => 'Visual or performing artist'],
            ['code' => 'MUSIC', 'name' => 'Musician', 'description' => 'Music professional'],
            ['code' => 'DANCE', 'name' => 'Dancer', 'description' => 'Traditional or modern dancer'],
            
            // Religious
            ['code' => 'MONK', 'name' => 'Buddhist Monk', 'description' => 'Buddhist clergy'],
            
            // Domestic & Care
            ['code' => 'HOUSE', 'name' => 'Housewife/Househusband', 'description' => 'Homemaker'],
            ['code' => 'CARE', 'name' => 'Caregiver', 'description' => 'Elderly or child care'],
            ['code' => 'MAID', 'name' => 'Domestic Helper', 'description' => 'Household worker'],
            
            // Other
            ['code' => 'STUD', 'name' => 'Student', 'description' => 'Currently studying'],
            ['code' => 'RET', 'name' => 'Retired', 'description' => 'Retired from work'],
            ['code' => 'UNEMP', 'name' => 'Unemployed', 'description' => 'Currently unemployed'],
            ['code' => 'OTHER', 'name' => 'Other', 'description' => 'Other occupation'],
        ];

        foreach ($occupations as $occupation) {
            Occupation::create([
                'code' => $occupation['code'],
                'name' => $occupation['name'],
                'description' => $occupation['description'],
                'status_id' => 1,
            ]);
        }
    }
}
