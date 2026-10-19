<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrFail();
        $employee = Employee::where('email', 'rina@secureattend.test')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'rina@secureattend.test'],
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'name' => 'Rina Kartika',
                'username' => 'rina',
                'role' => 'employee',
                'password' => bcrypt('password123'),
            ]
        );
    }
}
