<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id ?? $request->user()->employee?->company_id;

        return response()->json(
            Employee::where('company_id', $companyId)->get()
        );
    }
}
