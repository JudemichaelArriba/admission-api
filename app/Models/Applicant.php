<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone_number',
        'date_of_birth',
        'address',
        'status',
        'course_id',
    ];

    // Each applicant belongs to a course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Applicant can have many documents
    public function documents()
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    // Applicant can have many exams
    public function exams()
    {
        return $this->hasMany(EntranceExam::class);
    }
}