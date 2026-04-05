<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'course_name',
        'units',
        'department',
        'status',
        'type',
        'description',
    ];

    // One course has many applicants
    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
