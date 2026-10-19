<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkLocation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user?->company_id ?? $user?->employee?->company_id;

        if (! $companyId) {
            return response()->json(['stats' => [], 'recent' => [], 'locations' => []]);
        }

        $stats = [
            'employees' => Employee::where('company_id', $companyId)->count(),
            'present' => Attendance::where('company_id', $companyId)->whereDate('check_in_at', today())->count(),
            'late' => Attendance::where('company_id', $companyId)->whereDate('check_in_at', today())->whereNotNull('check_in_at')->count(),
            'locations' => WorkLocation::where('company_id', $companyId)->count(),
        ];

        $recent = Attendance::with('employee')
            ->where('company_id', $companyId)
            ->latest('check_in_at')
            ->limit(5)
            ->get()
            ->map(fn ($attendance) => [
                'name' => $attendance->employee?->full_name ?? 'Karyawan',
                'status' => ucfirst($attendance->status ?? 'present'),
                'time' => $attendance->check_in_at?->format('H:i') ?? '--:--',
            ])
            ->values();

        $locations = WorkLocation::where('company_id', $companyId)->get();

        return response()->json([
            'stats' => $stats,
            'recent' => $recent,
            'locations' => $locations,
        ]);
    }

    public function webDashboard()
    {
        $stats = [
            'employees' => Employee::count(),
            'present' => Attendance::whereDate('check_in_at', today())->count(),
            'late' => Attendance::whereDate('check_in_at', today())->count(),
            'locations' => WorkLocation::count(),
        ];

        $recent = Attendance::with('employee')
            ->latest('check_in_at')
            ->limit(5)
            ->get()
            ->map(fn ($attendance) => [
                'name' => $attendance->employee?->full_name ?? 'Karyawan',
                'status' => ucfirst($attendance->status ?? 'present'),
                'time' => $attendance->check_in_at?->format('H:i') ?? '--:--',
            ])
            ->values();

        $locations = WorkLocation::all();

        return view('dashboard', compact('stats', 'recent', 'locations'));
    }
}
