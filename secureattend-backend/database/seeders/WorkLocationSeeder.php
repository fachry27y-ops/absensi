<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkLocation;
use Illuminate\Database\Seeder;

class WorkLocationSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrFail();

        WorkLocation::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Kantor Pusat'],
            [
                'branch_name' => 'Jakarta Pusat',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius_meters' => 100,
                'max_accuracy_meters' => 30,
                'allowed_start_time' => '08:00',
                'allowed_end_time' => '17:00',
                'is_active' => true,
            ]
        );
    }
}
