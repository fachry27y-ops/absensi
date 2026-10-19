<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'work_location_id',
        'check_in_at',
        'check_out_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_photo_path',
        'check_out_photo_path',
        'status',
        'notes',
        'distance_meters',
        'risk_score',
        'is_fake_gps_detected',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'is_fake_gps_detected' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function workLocation()
    {
        return $this->belongsTo(WorkLocation::class);
    }
}
