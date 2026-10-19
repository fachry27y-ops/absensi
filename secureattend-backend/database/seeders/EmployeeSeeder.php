<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrFail();

        Employee::firstOrCreate(
            ['employee_number' => 'EMP-001'],
            [
                'company_id' => $company->id,
                'full_name' => 'Rina Kartika',
                'email' => 'rina@secureattend.test',
                'phone' => '081234567890',
                'position' => 'Frontend Developer',
                'division' => 'Engineering',
                'branch_name' => 'Jakarta Pusat',
                'status' => 'active',
            ]
        );
    }
}
