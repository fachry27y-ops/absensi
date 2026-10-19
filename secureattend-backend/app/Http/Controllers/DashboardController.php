<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user?->company_id ?? $user?->employee?->company_id;

        if (! $companyId) {
            return response()->json(['stats' => [], 'recent' => [], 'locations' => [], 'monthly' => ['labels' => [], 'values' => []], 'my_attendance' => null]);
        }

        $stats = [
            'employees' => Employee::where('company_id', $companyId)->count(),
            'present' => Attendance::where('company_id', $companyId)->whereDate('check_in_at', today())->count(),
            'late' => Attendance::where('company_id', $companyId)->whereDate('check_in_at', today())->where('status', 'pending_review')->count(),
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
        $monthly = $this->buildMonthlySummary($companyId);
        $todayAttendance = $user?->employee
            ? Attendance::where('employee_id', $user->employee->id)
                ->whereDate('check_in_at', today())
                ->first(['id', 'check_in_at', 'check_out_at', 'status'])
            : null;

        return response()->json([
            'stats' => $stats,
            'recent' => $recent,
            'locations' => $locations,
            'monthly' => $monthly,
            'my_attendance' => $todayAttendance,
        ]);
    }

    public function webDashboard()
    {
        $companyId = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id ?? Company::query()->value('id');

        $stats = [
            'employees' => Employee::where('company_id', $companyId)->count(),
            'present' => Attendance::where('company_id', $companyId)->whereDate('check_in_at', today())->count(),
            'late' => Attendance::where('company_id', $companyId)->whereDate('check_in_at', today())->where('status', 'pending_review')->count(),
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
        $monthly = $this->buildMonthlySummary($companyId);

        return view('dashboard', compact('stats', 'recent', 'locations', 'monthly'));
    }

    protected function buildMonthlySummary(?int $companyId): array
    {
        $baseQuery = Attendance::query();

        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $driver = DB::connection()->getDriverName();
        $monthExpression = match ($driver) {
            'sqlite' => "CAST(strftime('%m', check_in_at) AS INTEGER)",
            'mysql', 'mariadb', 'sqlsrv' => 'MONTH(check_in_at)',
            'pgsql' => 'CAST(EXTRACT(MONTH FROM check_in_at) AS INTEGER)',
            default => throw new \RuntimeException("Monthly attendance is not supported for database driver [{$driver}]."),
        };

        $monthlyData = $baseQuery
            ->where('check_in_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw($monthExpression.' as month_number')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month_number')
            ->toArray();

        $labels = [];
        $values = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $month = now()->subMonths($offset);
            $monthNumber = (int) $month->format('n');
            $labels[] = $month->translatedFormat('M');
            $values[] = (int) ($monthlyData[$monthNumber] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
