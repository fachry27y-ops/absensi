<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id ?? $request->user()->employee?->company_id;

        return response()->json(
            WorkLocation::where('company_id', $companyId)->get()
        );
    }
}
