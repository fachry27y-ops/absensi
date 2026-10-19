<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'branch_name',
        'latitude',
        'longitude',
        'radius_meters',
        'max_accuracy_meters',
        'allowed_start_time',
        'allowed_end_time',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
