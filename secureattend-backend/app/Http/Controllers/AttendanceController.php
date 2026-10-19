<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Akun tidak terhubung dengan data karyawan.'], 403);
        }

        $location = $this->resolveLocation($employee);
        $payload = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'photo' => ['required', 'string'],
            'mock_location_detected' => ['nullable', 'boolean'],
            'location_age_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $distance = $this->haversine((float) $payload['latitude'], (float) $payload['longitude'], (float) $location->latitude, (float) $location->longitude);
        $insideRadius = $distance <= (float) $location->radius_meters;
        $accuracyOk = (float) $payload['accuracy'] <= (float) $location->max_accuracy_meters;
        $mockDetected = (bool) ($payload['mock_location_detected'] ?? false);
        $staleLocation = isset($payload['location_age_ms']) && (int) $payload['location_age_ms'] > 60000;

        if (! $insideRadius || ! $accuracyOk || $mockDetected || $staleLocation) {
            return response()->json([
                'message' => 'Absensi ditolak karena lokasi tidak sesuai kebijakan perusahaan.',
                'distance_meters' => round($distance, 2),
                'inside_radius' => $insideRadius,
                'accuracy_ok' => $accuracyOk,
                'mock_location_detected' => $mockDetected,
                'stale_location' => $staleLocation,
            ], 422);
        }

        $attendanceExists = Attendance::where('employee_id', $employee->id)
            ->whereDate('check_in_at', today())
            ->exists();

        if ($attendanceExists) {
            return response()->json(['message' => 'Absensi masuk sudah dilakukan hari ini.'], 409);
        }

        $path = $this->storePhoto($payload['photo'], 'check-in');

        $record = Attendance::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'work_location_id' => $location->id,
            'check_in_at' => now(),
            'check_in_latitude' => $payload['latitude'],
            'check_in_longitude' => $payload['longitude'],
            'check_in_accuracy' => $payload['accuracy'],
            'check_in_photo_path' => $path,
            'distance_meters' => round($distance, 2),
            'status' => 'present',
            'risk_score' => 0,
        ]);

        return response()->json([
            'message' => 'Absensi masuk berhasil.',
            'attendance' => $record,
        ]);
    }

    public function checkOut(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Akun tidak terhubung dengan data karyawan.'], 403);
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();

        if (! $attendance) {
            return response()->json(['message' => 'Belum ada absensi masuk yang valid untuk checkout.'], 409);
        }

        $payload = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'photo' => ['required', 'string'],
            'mock_location_detected' => ['nullable', 'boolean'],
            'location_age_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $location = $this->resolveLocation($employee);
        $distance = $this->haversine((float) $payload['latitude'], (float) $payload['longitude'], (float) $location->latitude, (float) $location->longitude);
        $path = $this->storePhoto($payload['photo'], 'check-out');
        $mockDetected = (bool) ($payload['mock_location_detected'] ?? false);
        $staleLocation = isset($payload['location_age_ms']) && (int) $payload['location_age_ms'] > 60000;

        $attendance->update([
            'check_out_at' => now(),
            'check_out_photo_path' => $path,
            'status' => $distance <= (float) $location->radius_meters && ! $mockDetected && ! $staleLocation ? 'present' : 'pending_review',
            'risk_score' => $distance > (float) $location->radius_meters || $mockDetected || $staleLocation ? 50 : 0,
            'distance_meters' => round($distance, 2),
        ]);

        return response()->json([
            'message' => 'Absensi pulang berhasil.',
            'attendance' => $attendance->fresh(),
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if ($employee) {
            return response()->json(
                Attendance::where('employee_id', $employee->id)->latest('check_in_at')->get()
            );
        }

        return response()->json(
            Attendance::where('company_id', $user->company_id)->latest('check_in_at')->get()
        );
    }

    protected function resolveLocation(Employee $employee): WorkLocation
    {
        return WorkLocation::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    protected function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    protected function storePhoto(string $base64Image, string $prefix): string
    {
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Image);
        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            abort(422, 'Foto absensi tidak valid.');
        }

        $folder = 'attendance';
        $path = $folder.'/'.$prefix.'-'.uniqid().'.jpg';
        Storage::disk('public')->put($path, $decoded);

        return $path;
    }
}
